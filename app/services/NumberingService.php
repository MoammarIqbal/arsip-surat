<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class NumberingService
{
    public static function monthToRoman(int $month): string
    {
        $r = ["", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
        return $r[$month] ?? "";
    }

    /**
     * Dapatkan sequence berikutnya yang "rapat" (first missing positive)
     * berdasarkan semua surat pada (year, month) dari tanggal_surat.
     */
    protected function nextGaplessSequence(int $year, int $month): int
    {
        // Ambil semua sequence bulan tsb, lock baris untuk hindari race-condition
        $used = DB::table('letters')
            ->select('sequence')
            ->whereYear('tanggal_surat', $year)
            ->whereMonth('tanggal_surat', $month)
            ->orderBy('sequence')
            ->lockForUpdate()
            ->pluck('sequence')
            ->all();

        // Cari first missing positive integer (1,2,3,... yang hilang)
        $expected = 1;
        foreach ($used as $seq) {
            if ($seq > $expected)
                break; // ketemu gap
            if ($seq === $expected)
                $expected++;
        }
        return $expected; // kalau tidak ada gap, ini = max+1 atau 1 jika kosong
    }

    /**
     * Generate nomor & sequence (gapless per-bulan).
     * - Tetap reset otomatis tiap bulan (karena berdasarkan tanggal_surat).
     * - Aman untuk concurrency (pakai row lock di letter_counters).
     */
    public function generate(string $kodeKlasifikasi, \DateTimeInterface $tanggal): array
    {
        $year = (int) $tanggal->format('Y');
        $month = (int) $tanggal->format('n');

        return DB::transaction(function () use ($year, $month, $kodeKlasifikasi) {
            // 🔒 Lock semua baris letters di bulan & tahun ini agar aman dari race condition
            DB::table('letters')
                ->whereYear('tanggal_surat', $year)
                ->whereMonth('tanggal_surat', $month)
                ->lockForUpdate()
                ->get();

            // 🔍 Cari nomor urut terkecil yang belum digunakan (gapless)
            $next = $this->nextGaplessSequence($year, $month);

            // 🔢 Bentuk nomor surat
            $padded = str_pad((string) $next, 3, '0', STR_PAD_LEFT);
            $roman = self::monthToRoman($month);
            $nomor = "{$padded}/{$kodeKlasifikasi}/{$roman}/{$year}";

            return ['nomor' => $nomor, 'sequence' => $next];
        });
    }


    /**
     * Bantu ekstrak sequence dari nomor manual pengguna: "NNN/..."
     * Jika tidak valid, kembalikan null.
     */
    public static function sequenceFromManual(?string $nomor): ?int
    {
        if (!$nomor)
            return null;
        // ambil bagian sebelum '/' dan pastikan numerik
        $first = explode('/', $nomor, 2)[0] ?? '';
        if (preg_match('/^\d+$/', $first)) {
            return (int) $first;
        }
        return null;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Services\NumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LetterController extends Controller
{
    public function __construct(private NumberingService $numbering)
    {
    }

    // GET /api/surat
    public function index(Request $request)
    {
        $q = Letter::query()->with('user:id,name')->latest('id');

        if ($s = trim($request->query('search', ''))) {
            $q->where(function ($w) use ($s) {
                $w->where('nomor_surat', 'like', "%$s%")
                    ->orWhere('perihal', 'like', "%$s%")
                    ->orWhere('tujuan', 'like', "%$s%")
                    ->orWhere('kode_klasifikasi', 'like', "%$s%");
            });
        }

        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        return $q->paginate($perPage);
    }

    // GET /api/surat/{id}
    public function show(int $id)
    {
        $letter = Letter::with('user')->findOrFail($id);
        return response()->json($letter);
    }

    // POST /api/surat  (semua user login)
    public function store(Request $req)
    {
        $user = $req->user();

        $data = $req->validate([
            'nomor_surat'      => ['nullable', 'string', 'max:100', 'unique:letters,nomor_surat'],
            'tujuan'           => ['required', 'string', 'max:255'],
            'kode_klasifikasi' => ['required', 'string', 'max:50'],
            'perihal'          => ['required', 'string', 'max:255'],
            'tanggal_surat'    => ['required', 'date'],
            'tautan_dokumen'   => ['nullable', 'url'],
            // ⬇️ khusus: hanya PDF/JPG/JPEG/PNG (≤10MB)
            'file'             => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        return DB::transaction(function () use ($data, $user, $req) {
            $tanggal = new \DateTimeImmutable($data['tanggal_surat']);

            // Generate nomor + sequence
            if (empty($data['nomor_surat'])) {
                $g = app(NumberingService::class)->generate($data['kode_klasifikasi'], $tanggal);
                $nomor = $g['nomor'];
                $seq   = $g['sequence'];
            } else {
                $seqManual = NumberingService::sequenceFromManual($data['nomor_surat']);
                if (!$seqManual || $seqManual < 1) {
                    abort(422, 'Format nomor_surat tidak valid. Harus diawali angka urut, contoh: 003/KEU/XI/2025');
                }
                $year = (int) $tanggal->format('Y');
                $month = (int) $tanggal->format('n');
                $exists = DB::table('letters')
                    ->whereYear('tanggal_surat', $year)
                    ->whereMonth('tanggal_surat', $month)
                    ->where('sequence', $seqManual)
                    ->exists();
                if ($exists) abort(422, "Sequence {$seqManual} sudah terpakai di bulan tersebut.");

                $nomor = $data['nomor_surat'];
                $seq   = $seqManual;
            }

            $filePath = null;
            $link     = $data['tautan_dokumen'] ?? null;

            // Upload file jika ada (hanya pdf/jpg/jpeg/png)
            if ($req->hasFile('file')) {
                $uploaded = $req->file('file');
                if (!$uploaded || !$uploaded->isValid()) {
                    abort(422, 'File upload tidak valid.');
                }

                // nama file = nomor_surat (aman) + ekstensi asli
                $safeName  = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $nomor);
                $ext       = strtolower($uploaded->getClientOriginalExtension());
                $finalName = $safeName . ($ext ? ".{$ext}" : '');
                $dir       = 'letters';

                $filePath = $uploaded->storeAs($dir, $finalName, 'public'); // letters/xxx.ext
                $link     = null; // jika file ada, abaikan tautan
            }

            $id = DB::table('letters')->insertGetId([
                'nomor_surat'      => $nomor,
                'sequence'         => $seq,
                'tujuan'           => $data['tujuan'],
                'kode_klasifikasi' => $data['kode_klasifikasi'],
                'perihal'          => $data['perihal'],
                'tanggal_surat'    => $tanggal->format('Y-m-d'),
                'tautan_dokumen'   => $link,
                'file_path'        => $filePath,
                'user_id'          => $user->id,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            return response()->json(DB::table('letters')->where('id', $id)->first(), 201);
        });
    }

    // PUT /api/surat/{id}  (admin only via middleware)
    public function update(Request $req, int $id)
    {
        $user = $req->user();

        $data = $req->validate([
            'nomor_surat'      => ['nullable', 'string', 'max:100', 'unique:letters,nomor_surat,' . $id],
            'tujuan'           => ['sometimes', 'required', 'string', 'max:255'],
            'kode_klasifikasi' => ['sometimes', 'required', 'string', 'max:50'],
            'perihal'          => ['sometimes', 'required', 'string', 'max:255'],
            'tanggal_surat'    => ['sometimes', 'required', 'date'],
            'tautan_dokumen'   => ['nullable', 'url'],
            // ⬇️ khusus: hanya PDF/JPG/JPEG/PNG (≤10MB)
            'file'             => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ]);

        return DB::transaction(function () use ($id, $data, $req) {
            $row = DB::table('letters')->lockForUpdate()->find($id);
            abort_if(!$row, 404, 'Not found');

            $tanggalBaru = isset($data['tanggal_surat'])
                ? new \DateTimeImmutable($data['tanggal_surat'])
                : new \DateTimeImmutable($row->tanggal_surat);

            $kodeBaru = $data['kode_klasifikasi'] ?? $row->kode_klasifikasi;

            $nomor = $row->nomor_surat;
            $seq   = (int) $row->sequence;

            // Nomor manual?
            if (!empty($data['nomor_surat'])) {
                $seqManual = NumberingService::sequenceFromManual($data['nomor_surat']);
                if (!$seqManual || $seqManual < 1) {
                    abort(422, 'Format nomor_surat tidak valid.');
                }

                $year  = (int) $tanggalBaru->format('Y');
                $month = (int) $tanggalBaru->format('n');
                $exists = DB::table('letters')
                    ->where('id', '<>', $id)
                    ->whereYear('tanggal_surat', $year)
                    ->whereMonth('tanggal_surat', $month)
                    ->where('sequence', $seqManual)
                    ->exists();
                if ($exists) abort(422, "Sequence {$seqManual} sudah terpakai bulan tsb.");

                $nomor = $data['nomor_surat'];
                $seq   = $seqManual;
            } else {
                // Jika bulan/tahun berubah → re-generate gapless
                $oldY = (int) date('Y', strtotime($row->tanggal_surat));
                $oldM = (int) date('n', strtotime($row->tanggal_surat));
                $newY = (int) $tanggalBaru->format('Y');
                $newM = (int) $tanggalBaru->format('n');

                if ($oldY !== $newY || $oldM !== $newM) {
                    $g = app(NumberingService::class)->generate($kodeBaru, $tanggalBaru);
                    $nomor = $g['nomor'];
                    $seq   = $g['sequence'];
                }
            }

            // Dokumen
            $filePath = $row->file_path;
            $link     = $data['tautan_dokumen'] ?? $row->tautan_dokumen;

            if ($req->hasFile('file')) {
                $uploaded = $req->file('file');
                if (!$uploaded || !$uploaded->isValid()) {
                    abort(422, 'File upload tidak valid.');
                }

                // hapus file lama jika ada
                if ($filePath) Storage::disk('public')->delete($filePath);

                $safeName  = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $nomor);
                $ext       = strtolower($uploaded->getClientOriginalExtension());
                $finalName = $safeName . ($ext ? ".{$ext}" : '');
                $dir       = 'letters';

                $filePath = $uploaded->storeAs($dir, $finalName, 'public');
                $link     = null; // jika file baru diunggah, abaikan tautan
            }

            DB::table('letters')->where('id', $id)->update([
                'nomor_surat'      => $nomor,
                'sequence'         => $seq,
                'tujuan'           => $data['tujuan']           ?? $row->tujuan,
                'kode_klasifikasi' => $kodeBaru,
                'perihal'          => $data['perihal']          ?? $row->perihal,
                'tanggal_surat'    => $tanggalBaru->format('Y-m-d'),
                'tautan_dokumen'   => $link,
                'file_path'        => $filePath,
                'updated_at'       => now(),
            ]);

            return response()->json(DB::table('letters')->find($id));
        });
    }

    // DELETE /api/surat/{id} (admin only)
    public function destroy(int $id)
    {
        $letter = Letter::findOrFail($id);
        if ($letter->file_path) Storage::disk('public')->delete($letter->file_path);
        $letter->delete();
        return response()->json(['message' => 'Deleted']);
    }

    private static function monthToRoman(int $m): string
    {
        $r = ["", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
        return $r[$m] ?? "";
    }

    // GET /api/surat/next-number
    public function nextNumber(Request $request)
    {
        $request->validate([
            'kode_klasifikasi' => 'required|string|max:20',
            'tanggal_surat'    => 'required|date',
        ]);

        $kode  = strtoupper($request->kode_klasifikasi);
        $date  = Carbon::parse($request->tanggal_surat);
        $year  = (int) $date->format('Y');
        $month = (int) $date->format('n');

        $used = DB::table('letters')
            ->whereYear('tanggal_surat', $year)
            ->whereMonth('tanggal_surat', $month)
            ->pluck('sequence')
            ->all();

        $usedSet = array_fill_keys($used, true);
        $seq = 1; while (isset($usedSet[$seq])) { $seq++; }

        $padded = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        $roman  = self::monthToRoman($month);
        $nomor  = "{$padded}/{$kode}/{$roman}/{$year}";

        return response()->json([
            'nomor_surat' => $nomor,
            'sequence'    => $seq,
        ]);
    }

    public function download($id, Request $request): StreamedResponse
    {
        $letter = Letter::with('user')->findOrFail($id);
        if (!$letter->file_path) abort(404, 'Surat ini tidak memiliki file.');

        $safeName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $letter->nomor_surat);
        $ext      = pathinfo($letter->file_path, PATHINFO_EXTENSION);
        $filename = $safeName . ($ext ? ".{$ext}" : '');

        return Storage::disk('public')->download($letter->file_path, $filename);
    }
}

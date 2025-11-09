<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Letter extends Model
{
    protected $fillable = [
        'nomor_surat',
        'sequence',
        'tujuan',
        'kode_klasifikasi',
        'perihal',
        'tanggal_surat',
        'tautan_dokumen',
        'file_path',
        'user_id',
    ];

    protected $casts = [
        'tanggal_surat' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_surat')->unique();         // NNN/KODE/ROMAWI/YYYY
            $table->unsignedInteger('sequence');             // No urut (untuk sorting)
            $table->string('tujuan');
            $table->string('kode_klasifikasi');              // UMUM, KEU, ...
            $table->string('perihal');
            $table->date('tanggal_surat');
            $table->string('tautan_dokumen')->nullable();    // jika link
            $table->string('file_path')->nullable();         // jika upload file
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['tanggal_surat']);
            $table->index(['kode_klasifikasi']);
        });
    }
    public function down(): void {
        Schema::dropIfExists('letters');
    }
};

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLetterRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() != null; }

    public function rules(): array
    {
        return [
            'nomor_surat'      => ['nullable','string','max:100','unique:letters,nomor_surat'],
            'tujuan'           => ['required','string','max:255'],
            'kode_klasifikasi' => ['required', Rule::in(['UMUM','KEU','ADM','SDM','DIR','NDA-DIR','PKS-DIR'])],
            'perihal'          => ['required','string','max:255'],
            'tanggal_surat'    => ['required','date'],
            'tautan_dokumen'   => ['nullable','url'],
            'file'             => ['nullable','file','max:10240', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];
    }
}

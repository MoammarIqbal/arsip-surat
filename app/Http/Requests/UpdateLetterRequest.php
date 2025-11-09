<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLetterRequest extends FormRequest
{
    public function authorize(): bool { 
        // Hanya admin (kita cek via middleware di route), di sini cukup user login
        return $this->user() != null; 
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'nomor_surat'      => ['sometimes','string','max:100', Rule::unique('letters','nomor_surat')->ignore($id)],
            'tujuan'           => ['sometimes','string','max:255'],
            'kode_klasifikasi' => ['sometimes', Rule::in(['UMUM','KEU','ADM','SDM','DIR','NDA-DIR','PKS-DIR'])],
            'perihal'          => ['sometimes','string','max:255'],
            'tanggal_surat'    => ['sometimes','date'],
            'tautan_dokumen'   => ['nullable','url'],
            'file'             => ['nullable','file','max:10240', 'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/plain,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];
    }
}

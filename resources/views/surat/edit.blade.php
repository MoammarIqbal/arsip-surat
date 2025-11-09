@php($title = 'Edit Surat')
<x-layouts.app :title="$title">
  <div class="card p-6 max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-2xl font-bold">Edit Surat</h2>
      <a href="{{ route('surat.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Kembali</a>
    </div>

    <form id="formEdit" class="grid grid-cols-1 md:grid-cols-2 gap-4" enctype="multipart/form-data">
      <!-- Nomor Surat (boleh diedit atau dibiarkan kosong untuk auto-re-generate saat ganti tanggal) -->
      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Nomor Surat Lengkap</label>
        <input id="nomor_surat" class="input-style" placeholder="Contoh: 001/KEU/XI/2025 (opsional)">
        <p class="text-xs text-gray-500 mt-1">
          Kosongkan jika ingin mempertahankan nomor lama. Jika mengubah <b>tanggal</b> tanpa mengisi nomor, sistem dapat meng-generate nomor baru (sesuai bulan & klasifikasi).
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Tujuan Surat</label>
        <input id="tujuan" class="input-style" required>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Perihal/Subjek</label>
        <input id="perihal" class="input-style" required>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Kode Klasifikasi Surat</label>
        <select id="kode_klasifikasi" class="input-style" required>
          <option value="UMUM">UMUM (Umum)</option>
          <option value="KEU">KEU (Keuangan)</option>
          <option value="ADM">ADM (Administrasi)</option>
          <option value="SDM">SDM (Sumber Daya Manusia)</option>
          <option value="DIR">DIR (Direksi)</option>
          <option value="NDA-DIR">NDA-DIR (Non-Disclosure Agreement - Direksi)</option>
          <option value="PKS-DIR">PKS-DIR (Perjanjian Kerja Sama - Direksi)</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium mb-1">Tanggal Surat</label>
        <input type="date" id="tanggal_surat" class="input-style" required>
      </div>

      <div class="md:col-span-2">
        <label class="block text-sm font-medium mb-1">Dokumen Saat Ini</label>
        <div id="dokumen-sekarang" class="text-sm">
          <span class="text-gray-500">Memuat...</span>
        </div>
      </div>

      <div class="md:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1">Tautan Dokumen (opsional)</label>
          <input type="url" id="tautan_dokumen" class="input-style" placeholder="https://...">
          <p class="text-xs text-gray-500 mt-1">Jika Anda mengunggah file, tautan ini akan diabaikan.</p>
        </div>
        <div>
          <label class="block text-sm font-medium mb-1">Upload File Dokumen (opsional)</label>
          <input type="file" id="file" class="input-style" accept=".pdf,.doc,.docx,.txt,.xls,.xlsx">
          <p class="text-xs text-gray-500 mt-1">Maks 10 MB. Format: PDF/DOC/DOCX/TXT/XLS/XLSX.</p>
        </div>
      </div>

      <div class="md:col-span-2 flex items-center gap-3 mt-2">
        <button type="submit" class="btn-primary" id="btnSimpan">Update</button>
        <span id="msg" class="text-sm"></span>
      </div>
    </form>
  </div>

  @push('scripts')
  <script>
    // Admin-only
    ensureAdmin();

    const f = document.getElementById('formEdit');
    const msg = document.getElementById('msg');

    const nomor_surat      = document.getElementById('nomor_surat');
    const tujuan           = document.getElementById('tujuan');
    const perihal          = document.getElementById('perihal');
    const kode_klasifikasi = document.getElementById('kode_klasifikasi');
    const tanggal_surat    = document.getElementById('tanggal_surat');
    const tautan_dokumen   = document.getElementById('tautan_dokumen');
    const fileInput        = document.getElementById('file');
    const dokNow           = document.getElementById('dokumen-sekarang');

    // Ambil id dari URL: /surat/{id}/edit
    const m = location.pathname.match(/\/surat\/(\d+)\/edit$/);
    const suratId = m ? m[1] : null;
    if (!suratId) {
      alert('URL tidak valid');
      location.href = '{{ route('surat.index') }}';
    }

    function formatDokumen(r) {
      if (r?.tautan_dokumen) {
        return `<a class="text-blue-600 underline" target="_blank" href="${r.tautan_dokumen}">Buka Tautan</a>`;
      }
      if (r?.file_path) {
        return `<a class="text-blue-600 underline" target="_blank" href="{{ url('storage') }}/${r.file_path}">Unduh File</a>`;
      }
      return '<span class="text-gray-400">Tidak ada</span>';
    }

    async function loadDetail() {
      try {
        const r = await apiFetch('/api/surat/' + suratId, { method: 'GET' });
        nomor_surat.value       = r.nomor_surat || '';
        tujuan.value            = r.tujuan || '';
        perihal.value           = r.perihal || '';
        kode_klasifikasi.value  = r.kode_klasifikasi || 'UMUM';
        tanggal_surat.value     = r.tanggal_surat ? r.tanggal_surat.substring(0, 10) : '';
        tautan_dokumen.value    = r.tautan_dokumen || '';
        dokNow.innerHTML        = formatDokumen(r);
      } catch (e) {
        alert(e.message || 'Gagal memuat data');
        location.href = '{{ route('surat.index') }}';
      }
    }

    f.addEventListener('submit', async (e) => {
      e.preventDefault();
      msg.textContent = '';

      // Jika upload file -> gunakan POST + _method=PUT agar PHP mem-parsing $_FILES
      const hasFile = fileInput.files && fileInput.files.length > 0;
      if (hasFile) {
        const fd = new FormData();
        fd.append('_method', 'PUT');                    // <— penting (method override)
        if (nomor_surat.value.trim()) fd.append('nomor_surat', nomor_surat.value.trim());
        fd.append('tujuan', tujuan.value.trim());
        fd.append('perihal', perihal.value.trim());
        fd.append('kode_klasifikasi', kode_klasifikasi.value);
        fd.append('tanggal_surat', tanggal_surat.value);
        fd.append('file', fileInput.files[0]);
        // kirim tautan kosong agar backend mudah meng-null-kan
        fd.append('tautan_dokumen', '');               // opsional, backend bisa abaikan jika file ada

        try {
          await apiFetch('/api/surat/' + suratId, { method: 'POST', body: fd }); // <-- POST, bukan PUT
          msg.textContent = 'Berhasil diupdate';
          msg.className   = 'text-green-700 text-sm';
          setTimeout(() => location.href='{{ route('surat.index') }}', 800);
        } catch (e) {
          msg.textContent = e.message || 'Gagal update';
          msg.className   = 'text-red-600 text-sm';
        }
        return;
      }

      // Tanpa file -> kirim JSON via PUT biasa
      const payload = {
        tujuan: tujuan.value.trim(),
        perihal: perihal.value.trim(),
        kode_klasifikasi: kode_klasifikasi.value,
        tanggal_surat: tanggal_surat.value,
        tautan_dokumen: tautan_dokumen.value.trim() || null
      };
      if (nomor_surat.value.trim()) payload.nomor_surat = nomor_surat.value.trim();

      try {
        await apiFetch('/api/surat/' + suratId, {
          method: 'PUT',
          body: JSON.stringify(payload)
        });
        msg.textContent = 'Berhasil diupdate';
        msg.className   = 'text-green-700 text-sm';
        setTimeout(() => location.href='{{ route('surat.index') }}', 800);
      } catch (e) {
        msg.textContent = e.message || 'Gagal update';
        msg.className   = 'text-red-600 text-sm';
      }
    });

    loadDetail();
  </script>
  @endpush
</x-layouts.app>

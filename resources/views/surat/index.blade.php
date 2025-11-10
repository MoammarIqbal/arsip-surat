@php($title = 'Daftar Surat')
<x-layouts.app :title="$title">
  <div class="card p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-bold">Daftar Surat Keluar</h2>
        <p class="text-sm text-white/80 mt-1">Hanya menampilkan surat milik Anda (kecuali role: admin).</p>
      </div>
      <a href="{{ route('surat.create') }}" class="btn-primary">+ Tambah Surat</a>
    </div>

    <div class="mb-4">
      <input id="search" class="input-style" placeholder="Cari Nomor, Perihal, Tujuan, atau Kode Klasifikasi...">
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full" id="surat-table">
        <thead class="bg-gray-50">
          <tr id="thead-row">
            <th data-field="sequence" class="sortable px-4 py-2 text-left text-xs font-semibold text-gray-500 cursor-pointer">No</th>
            <th data-field="nomor_surat" class="sortable px-4 py-2 text-left text-xs font-semibold text-gray-500 cursor-pointer">Nomor Surat</th>
            <th data-field="tanggal_surat" class="sortable px-4 py-2 text-left text-xs font-semibold text-gray-500 cursor-pointer">Tanggal</th>
            <th data-field="perihal" class="sortable px-4 py-2 text-left text-xs font-semibold text-gray-500 cursor-pointer">Perihal</th>
            <th data-field="tujuan" class="sortable px-4 py-2 text-left text-xs font-semibold text-gray-500 cursor-pointer">Tujuan</th>
            <th data-field="user_name" class="sortable px-4 py-2 text-left text-xs font-semibold text-gray-500 cursor-pointer">Nama Penginput</th>
            <th class="px-4 py-2 text-center text-xs font-semibold text-gray-500">Dokumen</th>
            {{-- Kolom Aksi hanya untuk admin --}}
          </tr>
        </thead>
        <tbody id="rows" class="bg-white divide-y divide-gray-200"></tbody>
      </table>
    </div>

    <p id="empty" class="text-center text-gray-500 py-6 hidden">Belum ada data.</p>
  </div>

  {{-- PREVIEW MODAL --}}
  <div id="preview-modal" class="fixed inset-0 hidden z-50">
    <div class="absolute inset-0 bg-black/40" id="preview-bg"></div>
    <div class="relative mx-auto mt-10 bg-white rounded-xl shadow-2xl w-[95vw] max-w-6xl h-[85vh] flex flex-col">
      <div class="flex items-center justify-between px-4 py-3 border-b">
        <div class="font-semibold text-sm truncate" id="preview-title">Preview</div>
        <div class="flex items-center gap-2">
          <button id="btn-preview-download" class="px-3 py-1.5 rounded border hover:bg-gray-50 text-sm">Unduh</button>
          <button id="btn-preview-close" class="px-3 py-1.5 rounded bg-gray-800 text-white text-sm">Tutup</button>
        </div>
      </div>
      <div class="flex-1 overflow-auto" id="preview-body">
        {{-- PDF --}}
        <iframe id="preview-pdf" class="w-full h-full hidden" title="Preview PDF"></iframe>
        {{-- IMAGE --}}
        <div class="w-full h-full hidden p-2 overflow-auto place-items-center" id="preview-img-wrap" style="display:grid;">
          <img id="preview-img" class="max-w-full max-h-full rounded shadow" alt="Preview Gambar">
        </div>
        {{-- Fallback --}}
        <div id="preview-fallback" class="w-full h-full hidden grid place-items-center p-6 text-center text-gray-600">
          <div>
            <div class="text-lg font-semibold mb-1">Preview tidak didukung</div>
            <p class="text-sm">Hanya PDF, JPG, JPEG, PNG yang dapat dipreview. Silakan klik <b>Unduh</b> untuk membuka file.</p>
          </div>
        </div>
      </div>
    </div>
  </div>

  @push('scripts')
  <script>
    // ============ Auth guard ============
    if (!getToken()) { window.location.href = '{{ route('login.page') }}'; }

    const rows = document.getElementById('rows');
    const empty = document.getElementById('empty');
    const search = document.getElementById('search');
    const theadRow = document.getElementById('thead-row');
    const thElements = document.querySelectorAll('.sortable');

    // Preview modal refs
    const previewModal = document.getElementById('preview-modal');
    const previewBg = document.getElementById('preview-bg');
    const previewClose = document.getElementById('btn-preview-close');
    const previewTitle = document.getElementById('preview-title');
    const previewPDF = document.getElementById('preview-pdf');
    const previewImgWrap = document.getElementById('preview-img-wrap');
    const previewImg = document.getElementById('preview-img');
    const previewFallback = document.getElementById('preview-fallback');
    const previewDownloadBtn = document.getElementById('btn-preview-download');

    let cached = [];
    let isAdmin = false;
    let sortField = null;
    let sortAsc = true;
    let activePreviewUrl = null;

    const iconEdit = `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M11 5h2M4 21h4l11-11a2.828 2.828 0 10-4-4L4 17v4z"/>
      </svg>`;
    const iconTrash = `
      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-1-2H10m-1 2h6"/>
      </svg>`;

    function ensureActionHeader() {
      if (isAdmin && !document.getElementById('th-aksi')) {
        const th = document.createElement('th');
        th.id = 'th-aksi';
        th.className = 'px-4 py-2 text-right text-xs font-semibold text-gray-500';
        th.textContent = 'Aksi';
        theadRow.appendChild(th);
      }
    }

    function render(list) {
      if (!list.length) { rows.innerHTML = ''; empty.classList.remove('hidden'); return; }
      empty.classList.add('hidden');
      ensureActionHeader();

      rows.innerHTML = list.map((r, i) => {
        const tgl = new Date(r.tanggal_surat).toLocaleDateString('id-ID', { day:'2-digit', month:'long', year:'numeric' });
        const noUrut = r.sequence ?? (i + 1);
        const penginput = r.user?.name ?? r.user_name ?? '-';
        const ext = r.file_path ? (r.file_path.split('.').pop() || '').toLowerCase() : '';

        const doc = r.tautan_dokumen
          ? `<a href="${r.tautan_dokumen}" target="_blank" class="text-blue-600 underline">Lihat</a>`
          : (r.file_path
              ? `<div class="flex items-center justify-center gap-1">
                   <button type="button" class="px-2 py-1 border rounded hover:bg-gray-50 text-blue-700 btn-preview"
                     data-id="${r.id}" data-no="${r.nomor_surat}" data-ext="${ext}">Preview</button>
                   <button type="button" class="px-2 py-1 border rounded hover:bg-gray-50 text-blue-700 btn-download"
                     data-id="${r.id}" data-no="${r.nomor_surat}" data-ext="${ext}">Unduh</button>
                 </div>`
              : '<span class="text-gray-400">N/A</span>');

        const aksi = isAdmin
          ? `<td class="px-4 py-2 text-sm text-right">
               <a href="/surat/${r.id}/edit" class="px-2 py-1 border rounded hover:bg-gray-50 mr-1" title="Edit">${iconEdit}</a>
               <button data-id="${r.id}" data-no="${r.nomor_surat}" class="px-2 py-1 border rounded text-red-600 hover:bg-red-50 btn-del" title="Hapus">${iconTrash}</button>
             </td>`
          : '';

        return `
          <tr class="hover:bg-gray-50">
            <td class="px-4 py-2 text-sm">${noUrut}</td>
            <td class="px-4 py-2 text-sm font-semibold">${r.nomor_surat}</td>
            <td class="px-4 py-2 text-sm">${tgl}</td>
            <td class="px-4 py-2 text-sm">${r.perihal}</td>
            <td class="px-4 py-2 text-sm">${r.tujuan}</td>
            <td class="px-4 py-2 text-sm">${penginput}</td>
            <td class="px-4 py-2 text-sm text-center">${doc}</td>
            ${aksi}
          </tr>
        `;
      }).join('');

      if (isAdmin) {
        document.querySelectorAll('.btn-del').forEach(btn => {
          btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            const dataNo = btn.getAttribute('data-no');
            if (!confirm('Hapus surat ' + dataNo + ' ?')) return;
            try { await apiFetch('/api/surat/' + id, { method: 'DELETE' }); await loadData(); }
            catch (e) { alert(e.message || 'Gagal menghapus'); }
          });
        });
      }

      document.querySelectorAll('.btn-download').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id  = btn.getAttribute('data-id');
          const no  = btn.getAttribute('data-no');
          const ext = btn.getAttribute('data-ext');
          try { await downloadLetterFile(id, no, ext); }
          catch (e) { alert(e.message || 'Gagal mengunduh file'); }
        });
      });

      document.querySelectorAll('.btn-preview').forEach(btn => {
        btn.addEventListener('click', async () => {
          const id  = btn.getAttribute('data-id');
          const no  = btn.getAttribute('data-no');
          const ext = (btn.getAttribute('data-ext') || '').toLowerCase();
          try { await previewLetterFile(id, no, ext); }
          catch (e) { alert(e.message || 'Gagal menampilkan preview'); }
        });
      });
    }

    function safeName(s) { return s.replace(/[\/\\:\*\?"<>\|]/g, '-'); }

    async function fetchLetterBlob(id) {
      const headers = { 'Accept': 'application/octet-stream' };
      const token = getToken(); if (token) headers['Authorization'] = `Bearer ${token}`;
      const res = await fetch(`${API_BASE}/api/surat/${id}/download`, { headers });
      if (!res.ok) {
        let msg = `${res.status} ${res.statusText}`;
        try { const j = await res.json(); if (j?.message) msg = j.message; } catch {}
        throw new Error(msg);
      }
      const blob = await res.blob();
      const cd = res.headers.get('Content-Disposition') || '';
      const m = /filename\*=UTF-8''([^;]+)|filename="?([^"]+)"?/i.exec(cd);
      const filename = m ? decodeURIComponent(m[1] || m[2] || '') : '';
      const ctype = res.headers.get('content-type') || '';
      return { blob, filename, ctype };
    }

    async function downloadLetterFile(id, nomorSurat, extHint) {
      const { blob, filename } = await fetchLetterBlob(id);
      const fallback = `${safeName(nomorSurat)}${extHint ? '.'+extHint : ''}`;
      const finalName = filename || fallback;
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = finalName; document.body.appendChild(a); a.click(); a.remove();
      URL.revokeObjectURL(url);
    }

    function showOnly(which) {
      // which: 'pdf' | 'img' | 'fallback'
      previewPDF.classList.toggle('hidden', which !== 'pdf');
      previewImgWrap.classList.toggle('hidden', which !== 'img');
      previewFallback.classList.toggle('hidden', which !== 'fallback');
    }

    function isImageExt(ext) {
      return ['jpg','jpeg','png'].includes((ext||'').toLowerCase());
    }

    async function previewLetterFile(id, nomorSurat, extHint) {
      const { blob, filename, ctype } = await fetchLetterBlob(id);
      previewTitle.textContent = filename || `${nomorSurat}${extHint ? '.'+extHint : ''}`;

      // Bersihkan URL sebelumnya
      if (activePreviewUrl) { URL.revokeObjectURL(activePreviewUrl); activePreviewUrl = null; }

      const ext = (extHint || '').toLowerCase();
      const isPdf  = ctype.includes('pdf') || ext === 'pdf';
      const isImg  = ctype.startsWith('image/') || isImageExt(ext);

      if (isPdf) {
        activePreviewUrl = URL.createObjectURL(blob);
        previewPDF.src = activePreviewUrl;
        showOnly('pdf');
      } else if (isImg) {
        activePreviewUrl = URL.createObjectURL(blob);
        previewImg.src = activePreviewUrl;
        showOnly('img');
      } else {
        showOnly('fallback');
      }

      previewDownloadBtn.onclick = async () => {
        const hint = ext || (isPdf ? 'pdf' : (isImg ? ext : ''));
        await downloadLetterFile(id, nomorSurat, hint);
      };
      previewModal.classList.remove('hidden');
    }

    function closePreview() {
      previewModal.classList.add('hidden');
      previewPDF.src = 'about:blank';
      previewImg.src = '';
      if (activePreviewUrl) { URL.revokeObjectURL(activePreviewUrl); activePreviewUrl = null; }
    }
    previewClose.addEventListener('click', closePreview);
    previewBg.addEventListener('click', closePreview);
    document.addEventListener('keydown', (e)=>{ if(e.key==='Escape' && !previewModal.classList.contains('hidden')) closePreview(); });

    function sortData(field) {
      if (sortField === field) sortAsc = !sortAsc; else { sortField = field; sortAsc = true; }
      cached.sort((a, b) => {
        let v1 = a[field] ?? ''; let v2 = b[field] ?? '';
        if (field === 'tanggal_surat'){ v1 = new Date(v1); v2 = new Date(v2); }
        else { v1 = v1.toString().toLowerCase(); v2 = v2.toString().toLowerCase(); }
        if (v1 < v2) return sortAsc ? -1 : 1;
        if (v1 > v2) return sortAsc ? 1 : -1;
        return 0;
      });
      render(cached); updateSortIcons();
    }
    function updateSortIcons() {
      thElements.forEach(th => {
        const field = th.dataset.field;
        th.innerHTML = th.textContent.split(' ')[0];
        if (field === sortField) th.innerHTML += ` <span class="text-gray-400">${sortAsc ? '▲' : '▼'}</span>`;
      });
    }

    async function loadData(qStr = '') {
      try {
        const url = qStr ? `/api/surat?per_page=100&search=${encodeURIComponent(qStr)}` : `/api/surat?per_page=100`;
        const res = await apiFetch(url, { method: 'GET' });
        cached = Array.isArray(res) ? res : (res.data || []);
        render(cached);
      } catch (e) {
        rows.innerHTML = `<tr><td colspan="${isAdmin ? 8 : 7}" class="px-4 py-6 text-center text-red-600">${e.message}</td></tr>`;
      }
    }

    async function init() {
      try { const me = await apiFetch('/api/profile'); isAdmin = (me.role === 'admin'); } catch (e) { isAdmin = false; }
      thElements.forEach(th => th.addEventListener('click', () => sortData(th.dataset.field)));
      await loadData();
    }

    search.addEventListener('input', async (e) => {
      const q = e.target.value.trim();
      if (!q) return render(cached);
      await loadData(q);
    });

    init();
  </script>
  @endpush
</x-layouts.app>

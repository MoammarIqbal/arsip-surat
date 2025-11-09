<x-layouts.app :title="'Manajemen User'">
  <div class="card p-6">
    <div class="flex items-center justify-between mb-4">
      <div>
        <h2 class="text-2xl font-bold">Manajemen User</h2>
        <p class="text-sm text-gray-600">Hanya admin yang dapat mengakses.</p>
      </div>
      <a href="{{ route('users.create') }}" class="btn-primary">+ Tambah User</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
      <input id="q" class="input-style" placeholder="Cari nama/email...">
      <select id="frole" class="input-style">
        <option value="">Semua role</option>
        <option value="admin">Admin</option>
        <option value="user">User</option>
      </select>
      <button id="btn-search" class="btn-primary">Cari</button>
    </div>

    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">ID</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Nama</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Email</th>
            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Role</th>
            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Aksi</th>
          </tr>
        </thead>
        <tbody id="rows" class="bg-white divide-y divide-gray-200"></tbody>
      </table>
    </div>

    <div class="flex items-center justify-between mt-4">
      <button id="prev" class="px-3 py-2 border rounded hover:bg-gray-50">Prev</button>
      <span id="pageinfo" class="text-sm text-gray-600"></span>
      <button id="next" class="px-3 py-2 border rounded hover:bg-gray-50">Next</button>
    </div>

    <p id="empty" class="text-center text-gray-500 py-6 hidden">Belum ada data.</p>
  </div>

  @push('scripts')
  <script>
    // Hanya admin
    ensureAdmin();

    const rows = document.getElementById('rows');
    const empty = document.getElementById('empty');
    const q = document.getElementById('q');
    const frole = document.getElementById('frole');
    const btnSearch = document.getElementById('btn-search');
    const btnPrev = document.getElementById('prev');
    const btnNext = document.getElementById('next');
    const pageInfo = document.getElementById('pageinfo');

    let currentUrl = '/api/users?per_page=10';
    let lastMeta = null;
    let meCache = null;

    // ambil profil utk guard hapus diri sendiri
    (async () => { try { meCache = await apiFetch('/api/profile'); } catch(e){} })();

    function render(list){
      if (!list.length){
        rows.innerHTML = '';
        empty.classList.remove('hidden');
        return;
      }
      empty.classList.add('hidden');

      rows.innerHTML = list.map(u => `
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-2 text-sm">${u.id}</td>
          <td class="px-4 py-2 text-sm">${u.name}</td>
          <td class="px-4 py-2 text-sm">${u.email}</td>
          <td class="px-4 py-2 text-sm">${u.role}</td>
          <td class="px-4 py-2 text-sm text-right">
            <a href="/admin/users/${u.id}/edit" class="px-3 py-1 border rounded hover:bg-gray-50">Edit</a>
            <button data-id="${u.id}" data-name="${u.name}" data-role="${u.role}"
              class="px-3 py-1 ml-2 border rounded text-red-600 hover:bg-red-50 btn-del">Hapus</button>
          </td>
        </tr>
      `).join('');

      // binding delete
      document.querySelectorAll('.btn-del').forEach(b => {
        b.addEventListener('click', async () => {
          const id = Number(b.getAttribute('data-id'));
          const name = b.getAttribute('data-name');
          const role = b.getAttribute('data-role') || '';

          // guard: jangan hapus diri sendiri dari UI
          if (meCache && meCache.id === id) {
            alert('Tidak bisa menghapus akun sendiri.');
            return;
          }
          if (!confirm('Hapus user ' + name + '?')) return;

          try{
            await apiFetch('/api/users/' + id, { method:'DELETE' });
            alert('Berhasil dihapus');
            load(currentUrl);
          }catch(e){
            alert(e.message || 'Gagal menghapus');
          }
        });
      });
    }

    async function load(url){
      try{
        const res = await apiFetch(url, { method:'GET' });
        const list = Array.isArray(res) ? res : (res.data || []);
        render(list);
        lastMeta = res.meta || null;
        if (lastMeta){
          pageInfo.textContent = `Halaman ${lastMeta.current_page} / ${lastMeta.last_page} (total ${lastMeta.total})`;
          btnPrev.disabled = lastMeta.current_page <= 1;
          btnNext.disabled = lastMeta.current_page >= lastMeta.last_page;
        } else {
          pageInfo.textContent = '';
          btnPrev.disabled = true;
          btnNext.disabled = true;
        }
        currentUrl = url;
      }catch(e){
        rows.innerHTML = `<tr><td colspan="5" class="px-4 py-6 text-center text-red-600">${e.message}</td></tr>`;
      }
    }

    btnSearch.addEventListener('click', ()=>{
      const params = new URLSearchParams();
      params.set('per_page', '10');
      if (q.value.trim()) params.set('search', q.value.trim());
      if (frole.value) params.set('role', frole.value);
      load('/api/users?' + params.toString());
    });

    btnPrev.addEventListener('click', ()=>{
      if (!lastMeta) return;
      if (lastMeta.current_page > 1){
        const u = new URL(currentUrl, window.location.origin);
        u.searchParams.set('page', lastMeta.current_page - 1);
        load(u.pathname + u.search);
      }
    });

    btnNext.addEventListener('click', ()=>{
      if (!lastMeta) return;
      if (lastMeta.current_page < lastMeta.last_page){
        const u = new URL(currentUrl, window.location.origin);
        u.searchParams.set('page', lastMeta.current_page + 1);
        load(u.pathname + u.search);
      }
    });

    // initial load
    load(currentUrl);
  </script>
  @endpush
</x-layouts.app>

<x-layouts.app :title="'Edit User'">
  <div class="card p-6 max-w-xl mx-auto">
    <h2 class="text-2xl font-bold mb-4">Edit User</h2>

    <form id="f" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1">Nama</label>
        <input id="name" class="input-style" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input id="email" type="email" class="input-style" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Password (opsional)</label>
        <input id="password" type="password" class="input-style" placeholder="Kosongkan jika tidak diganti">
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Role</label>
        <select id="role" class="input-style" required>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="flex items-center gap-3">
        <button class="btn-primary" type="submit">Update</button>
        <a class="px-4 py-2 border rounded hover:bg-gray-50" href="{{ route('users.index') }}">Kembali</a>
        <span id="msg" class="text-sm"></span>
      </div>
    </form>
  </div>

  @push('scripts')
  <script>
    ensureAdmin();

    // ambil id dari URL /admin/users/{id}/edit
    const pathParts = window.location.pathname.split('/');
    const userId = pathParts[pathParts.length - 2]; // .../users/{id}/edit

    async function loadDetail(){
      try{
        const u = await apiFetch('/api/users/' + userId);
        document.getElementById('name').value = u.name;
        document.getElementById('email').value = u.email;
        document.getElementById('role').value = u.role;
      }catch(e){
        alert(e.message || 'Gagal memuat data');
        window.location.href='{{ route('users.index') }}';
      }
    }

    const f = document.getElementById('f');
    const msg = document.getElementById('msg');

    f.addEventListener('submit', async (e)=>{
      e.preventDefault();
      msg.textContent = '';

      const payload = {
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim(),
        role: document.getElementById('role').value
      };
      const pass = document.getElementById('password').value;
      if (pass) payload.password = pass;

      try{
        await apiFetch('/api/users/' + userId, {
          method: 'PUT',
          body: JSON.stringify(payload)
        });
        msg.textContent = 'Berhasil diupdate';
        msg.className = 'text-green-700 text-sm';
        setTimeout(()=> window.location.href='{{ route('users.index') }}', 900);
      }catch(e){
        msg.textContent = e.message || 'Gagal update';
        msg.className = 'text-red-600 text-sm';
      }
    });

    loadDetail();
  </script>
  @endpush
</x-layouts.app>

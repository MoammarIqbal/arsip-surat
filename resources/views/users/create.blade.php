<x-layouts.app :title="'Tambah User'">
  <div class="card p-6 max-w-xl mx-auto">
    <h2 class="text-2xl font-bold mb-4">Tambah User</h2>
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
        <label class="block text-sm font-medium mb-1">Password</label>
        <input id="password" type="password" class="input-style" minlength="6" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Role</label>
        <select id="role" class="input-style" required>
          <option value="user">User</option>
          <option value="admin">Admin</option>
        </select>
      </div>

      <div class="flex items-center gap-3">
        <button class="btn-primary" type="submit">Simpan</button>
        <a class="px-4 py-2 border rounded hover:bg-gray-50" href="{{ route('users.index') }}">Kembali</a>
        <span id="msg" class="text-sm"></span>
      </div>
    </form>
  </div>

  @push('scripts')
  <script>
    ensureAdmin();

    const f = document.getElementById('f');
    const msg = document.getElementById('msg');

    f.addEventListener('submit', async (e)=>{
      e.preventDefault();
      msg.textContent = '';

      const payload = {
        name: document.getElementById('name').value.trim(),
        email: document.getElementById('email').value.trim(),
        password: document.getElementById('password').value,
        role: document.getElementById('role').value
      };

      try{
        const res = await apiFetch('/api/users', {
          method: 'POST',
          body: JSON.stringify(payload)
        });
        msg.textContent = 'Berhasil disimpan';
        msg.className = 'text-green-700 text-sm';
        setTimeout(()=> window.location.href='{{ route('users.index') }}', 900);
      }catch(e){
        msg.textContent = e.message || 'Gagal menyimpan';
        msg.className = 'text-red-600 text-sm';
      }
    });
  </script>
  @endpush
</x-layouts.app>

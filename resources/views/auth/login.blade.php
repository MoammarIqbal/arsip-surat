@php($title = 'Login')
<x-layouts.app :title="$title">
  <div class="max-w-md mx-auto card p-6">
    <h1 class="text-2xl font-bold mb-1 text-gray-800">Masuk</h1>
    <p class="text-gray-500 mb-6">Silakan login untuk mengakses Sistem Arsip Surat.</p>

    <form id="login-form" class="space-y-4">
      <div>
        <label class="block text-sm font-medium mb-1">Email</label>
        <input id="email" type="email" class="input-style" placeholder="masukan email" required>
      </div>
      <div>
        <label class="block text-sm font-medium mb-1">Password</label>
        <input id="password" type="password" class="input-style" placeholder="•••••••" required>
      </div>
      <button class="btn-primary w-full" type="submit">Login</button>
      <p id="error" class="text-red-600 text-sm"></p>
    </form>
  </div>

  @push('scripts')
    <script>
      // Jika sudah punya token, langsung ke daftar surat
      if (getToken()) { window.location.href = '{{ route('surat.index') }}'; }

      document.getElementById('login-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value.trim();
        const err = document.getElementById('error'); err.textContent = '';

        try {
          const data = await apiFetch('/api/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
          });
          setToken(data.token);
          if (window.notify) notify.success(data.message || 'Berhasil Login');
          window.location.href = '{{ route('surat.index') }}';
        } catch (e) {
          err.textContent = e.message || 'Gagal login';
        }
      });
    </script>
  @endpush
</x-layouts.app>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{{ $title ?? 'Sistem Arsip Surat' }}</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap" rel="stylesheet">

  {{-- Favicon & App Icon --}}
  <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
  <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">

  {{-- CSS eksternal --}}
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="min-h-screen">

  <!-- Top progress bar -->
  <div id="topbar"></div>

  <!-- Global loader -->
  <div id="app-loader">
    <div class="spinner" aria-label="Loading..."></div>
  </div>

  <!-- Toast container -->
  <div id="toast-wrap" aria-live="polite" aria-atomic="true"></div>

  <!-- Confirm modal -->
  <div id="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
    <div class="bg"></div>
    <div class="box">
      <h3 id="confirm-title" class="text-lg font-bold">Konfirmasi</h3>
      <p id="confirm-text" class="mt-1 text-sm text-gray-600">Yakin ingin melanjutkan?</p>
      <div class="actions">
        <button id="confirm-cancel" class="px-3 py-2 rounded border hover:bg-gray-100">Batal</button>
        <button id="confirm-ok" class="px-3 py-2 rounded text-white" style="background:var(--brand)">Ya, Lanjut</button>
      </div>
    </div>
  </div>

  <!-- NAVBAR -->
  <nav class="nav">
    <div class="max-w-6xl mx-auto px-4">
      <div class="flex h-14 items-center justify-between">
        <!-- brand -->
        <a href="{{ route('surat.index') }}" class="flex items-center gap-3 font-bold">
          <img src="{{ asset('images/transferku-logo.png') }}" alt="Logo TransferKu" class="object-contain"
            style="height: 10rem; width: auto;" {{-- sekitar setara teks ukuran text-xl --}} loading="lazy">
          <span class="text-xl md:text-2xl">Sistem Arsip Surat</span>
        </a>


        <!-- Desktop menu -->
        <div class="hidden md:flex items-center justify-end gap-1 nav-inner">
          <a href="{{ route('surat.index') }}" class="nav-link" data-path="/surat">Daftar Surat</a>
          <a href="{{ route('surat.create') }}" class="nav-link" data-path="/surat/tambah">Tambah Surat</a>
          <a href="{{ route('users.index') }}" id="menu-admin-desktop" class="nav-link hidden"
            data-path="/users">Manajemen User</a>

          <!-- User dropdown -->
          <div class="relative ml-2">
            <button id="btn-user-menu" class="btn-ghost inline-flex items-center gap-2">
              <span id="user-badge" class="text-sm text-gray-700">Memuat...</span>
              <svg class="w-4 h-4 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
              </svg>
            </button>
            <div id="user-dropdown"
              class="dropdown hidden absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border p-2">
              <a href="{{ route('surat.index') }}" class="nav-link w-full" data-path="/surat">Dashboard</a>
              <button id="btn-logout" class="nav-link w-full text-left">Logout</button>
            </div>
          </div>
        </div>

        <!-- Mobile toggler -->
        <button id="btn-menu" class="md:hidden inline-flex items-center justify-center p-2 rounded hover:bg-gray-100"
          aria-controls="mobile-menu" aria-expanded="false" aria-label="Toggle navigation">
          <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Mobile menu -->
    <div id="mobile-menu" class="md:hidden hidden border-t border-white/40">
      <div class="px-4 py-3 drawer">
        <div class="flex items-center justify-between pb-2 mb-2 border-b">
          <div class="flex items-center gap-2">
            {{-- Avatar kecil bisa diganti logo juga jika mau --}}
            <img src="{{ asset('images/transferku-logo.png') }}" alt="Logo TransferKu"
              class="w-8 h-8 rounded-full object-cover bg-white" loading="lazy">
            <div>
              <div id="user-badge-mobile" class="text-sm font-semibold">Memuat...</div>
              <div class="text-xs text-gray-500">Sistem Arsip Surat</div>
            </div>
          </div>
          <button id="btn-logout-mobile" class="px-3 py-2 rounded border hover:bg-gray-100">Logout</button>
        </div>

        <nav class="flex flex-col gap-1">
          <a href="{{ route('surat.index') }}" class="nav-link" data-path="/surat">Daftar Surat</a>
          <a href="{{ route('surat.create') }}" class="nav-link" data-path="/surat/tambah">Tambah Surat</a>
          <a href="{{ route('users.index') }}" id="menu-admin-mobile" class="nav-link hidden"
            data-path="/users">Manajemen User</a>
        </nav>
      </div>
    </div>
  </nav>

  <main class="max-w-6xl mx-auto px-4 py-8">
    {{ $slot }}
  </main>

  {{-- JS eksternal --}}
  <script src="{{ asset('js/app.js') }}"></script>
  @stack('scripts')
</body>

</html>
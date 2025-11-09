@php($title = 'Input Surat')
<x-layouts.app :title="$title">
    <div class="card p-6 max-w-3xl mx-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold">Input Surat Baru</h2>
            <a href="{{ route('surat.index') }}" class="px-4 py-2 border rounded hover:bg-gray-50">Kembali</a>
        </div>

        <form id="formCreate" class="grid grid-cols-1 md:grid-cols-2 gap-4" enctype="multipart/form-data">
            {{-- Nomor Surat Lengkap (auto, tapi boleh diedit) --}}
            <div class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Nomor Surat Lengkap</label>
                <input id="nomor_surat" class="input-style" placeholder="(Kosongkan: akan digenerate otomatis)">
                <p class="text-xs text-gray-500 mt-1">
                    Nilai ini otomatis disarankan dari Kode Klasifikasi &amp; Tanggal. Anda tetap bisa mengubah manual.
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Tujuan Surat</label>
                <input id="tujuan" class="input-style" required placeholder="Contoh: Divisi Pemasaran">
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">Perihal/Subjek</label>
                <input id="perihal" class="input-style" required placeholder="Contoh: Permintaan Laporan Bulanan">
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

            {{-- Pilihan sumber dokumen: Link atau File --}}
            <div class="md:col-span-2">
                <span class="block text-sm font-medium mb-1">Sumber Dokumen</span>
                <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2">
                        <input type="radio" name="docmode" value="link" checked>
                        <span>Tautan Dokumen</span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="radio" name="docmode" value="file">
                        <span>Upload File</span>
                    </label>
                </div>
            </div>

            {{-- Mode: Link --}}
            <div id="wrap-link" class="md:col-span-2">
                <label class="block text-sm font-medium mb-1">Tautan Dokumen (opsional)</label>
                <input type="url" id="tautan_dokumen" class="input-style" placeholder="https://contoh.com/surat.pdf">
                <p class="text-xs text-gray-500 mt-1">Jika mengunggah file, tautan ini akan diabaikan.</p>
            </div>

            {{-- Mode: File --}}
            <div id="wrap-file" class="md:col-span-2 hidden">
                <label class="block text-sm font-medium mb-1">Upload File Dokumen (opsional)</label>
                <input type="file" id="file" class="input-style" accept=".pdf,.doc,.docx,.txt,.xls,.xlsx">
                <p class="text-xs text-gray-500 mt-1">Maks 10 MB. Format: PDF/DOC/DOCX/TXT/XLS/XLSX.</p>
            </div>

            <div class="md:col-span-2 flex items-center gap-3 mt-2">
                <button type="submit" class="btn-primary inline-flex items-center gap-2" id="btnSimpan">
                    <span
                        class="spinner hidden w-4 h-4 border-2 border-white/80 border-t-transparent rounded-full animate-spin"></span>
                    <span>Simpan</span>
                </button>
                <span id="msg" class="text-sm"></span>
            </div>
        </form>
    </div>

    @push('scripts')
        <script>
            // butuh login
            if (!getToken()) { window.location.href = '{{ route('login.page') }}'; }

            const f = document.getElementById('formCreate');
            const msg = document.getElementById('msg');
            const btn = document.getElementById('btnSimpan');
            const spinner = btn.querySelector('.spinner');

            const nomor_surat = document.getElementById('nomor_surat');
            const tujuan = document.getElementById('tujuan');
            const perihal = document.getElementById('perihal');
            const kode_klasifikasi = document.getElementById('kode_klasifikasi');
            const tanggal_surat = document.getElementById('tanggal_surat');
            const tautan_dokumen = document.getElementById('tautan_dokumen');
            const fileInput = document.getElementById('file');

            const wrapLink = document.getElementById('wrap-link');
            const wrapFile = document.getElementById('wrap-file');

            // Toggle dokumen
            document.querySelectorAll('input[name="docmode"]').forEach(r => {
                r.addEventListener('change', () => {
                    const mode = document.querySelector('input[name="docmode"]:checked').value;
                    if (mode === 'link') {
                        wrapLink.classList.remove('hidden');
                        wrapFile.classList.add('hidden');
                        fileInput.value = '';                // bersihkan file saat pindah ke link
                    } else {
                        wrapFile.classList.remove('hidden');
                        wrapLink.classList.add('hidden');
                        tautan_dokumen.value = '';          // bersihkan link saat pindah ke file
                    }
                });
            });

            // ===== Preview nomor otomatis (dengan deteksi manual) =====
            const romans = ["", "I", "II", "III", "IV", "V", "VI", "VII", "VIII", "IX", "X", "XI", "XII"];
            let autoNumber = null;   // saran otomatis terakhir
            let manualNumber = false;  // user mengetik manual?

            // Jika user mengetik di field nomor -> jadikan manual.
            nomor_surat.addEventListener('input', () => {
                manualNumber = nomor_surat.value.trim().length > 0;
                if (!manualNumber && autoNumber) {
                    // Jika dikosongkan, kembali pakai auto
                    nomor_surat.value = autoNumber;
                }
            });

            function fallbackPreview(kode, tgl) {
                if (!tgl) {
                    nomor_surat.placeholder = '(Otomatis saat simpan)';
                    autoNumber = null;
                    if (!manualNumber) nomor_surat.value = '';
                    return;
                }
                const d = new Date(tgl);
                const rom = romans[d.getMonth() + 1] || '';
                const yr = d.getFullYear();
                const suggest = `AUTO/${kode || 'UMUM'}/${rom}/${yr}`;
                nomor_surat.placeholder = suggest;
                autoNumber = suggest;
                if (!manualNumber) nomor_surat.value = suggest;
            }

            let debounceTimer = null;
            async function fetchNextNumber() {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(async () => {
                    const kode = kode_klasifikasi.value;
                    const tgl = tanggal_surat.value;
                    if (!tgl) { fallbackPreview(kode, tgl); return; }

                    try {
                        const res = await apiFetch(
                            `/api/surat/next-number?kode_klasifikasi=${encodeURIComponent(kode)}&tanggal_surat=${encodeURIComponent(tgl)}`
                        );
                        const suggested = res?.nomor_surat || null;

                        nomor_surat.placeholder = suggested || '(Otomatis saat simpan)';
                        autoNumber = suggested;

                        // Timpa value hanya jika belum manual
                        if (!manualNumber) {
                            nomor_surat.value = suggested || '';
                        }
                    } catch (e) {
                        // Jika gagal (mis. offline), pakai fallback lokal
                        fallbackPreview(kode, tgl);
                    }
                }, 200);
            }

            kode_klasifikasi.addEventListener('change', fetchNextNumber);
            tanggal_surat.addEventListener('change', fetchNextNumber);

            // default tanggal = hari ini + preview awal (belum manual)
            (function preset() {
                const today = new Date().toISOString().slice(0, 10);
                tanggal_surat.value = today;
                manualNumber = false;
                fetchNextNumber();
            })();

            function setLoading(on) { btn.disabled = on; spinner.classList.toggle('hidden', !on); }
            function setMsg(text, ok = true) {
                msg.textContent = text || '';
                msg.className = ok ? 'text-green-700 text-sm' : 'text-red-600 text-sm';
            }

            // ===== Submit =====
            f.addEventListener('submit', async (e) => {
                e.preventDefault();
                setMsg(''); setLoading(true);

                const mode = document.querySelector('input[name="docmode"]:checked').value;

                // Validasi dasar
                if (!tujuan.value.trim() || !perihal.value.trim() || !tanggal_surat.value) {
                    setLoading(false);
                    return setMsg('Tujuan, Perihal, dan Tanggal wajib diisi.', false);
                }

                if (mode === 'link' && !tautan_dokumen.value.trim()) {
                    setLoading(false);
                    return setMsg('Isi tautan dokumen atau pindah ke mode Upload File.', false);
                }
                if (mode === 'file' && !(fileInput.files && fileInput.files.length)) {
                    setLoading(false);
                    return setMsg('Pilih file dokumen atau pindah ke mode Tautan.', false);
                }

                try {
                    if (mode === 'file' && fileInput.files && fileInput.files.length > 0) {
                        const fd = new FormData();
                        if (nomor_surat.value.trim()) fd.append('nomor_surat', nomor_surat.value.trim());
                        fd.append('tujuan', tujuan.value.trim());
                        fd.append('perihal', perihal.value.trim());
                        fd.append('kode_klasifikasi', kode_klasifikasi.value);
                        fd.append('tanggal_surat', tanggal_surat.value);
                        fd.append('file', fileInput.files[0]);
                        await apiFetch('/api/surat', { method: 'POST', body: fd });
                    } else {
                        const payload = {
                            tujuan: tujuan.value.trim(),
                            perihal: perihal.value.trim(),
                            kode_klasifikasi: kode_klasifikasi.value,
                            tanggal_surat: tanggal_surat.value,
                            tautan_dokumen: (tautan_dokumen.value.trim() || null)
                        };
                        if (nomor_surat.value.trim()) payload.nomor_surat = nomor_surat.value.trim();
                        await apiFetch('/api/surat', { method: 'POST', body: JSON.stringify(payload) });
                    }

                    setMsg('Berhasil disimpan', true);
                    setTimeout(() => { window.location.href = '{{ route('surat.index') }}'; }, 700);
                } catch (e) {
                    setMsg(e.message || 'Gagal menyimpan', false);
                } finally {
                    setLoading(false);
                }
            });
        </script>
    @endpush
</x-layouts.app>
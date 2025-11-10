# 📄 Sistem Arsip Surat Gramindo

**Sistem Arsip Surat Gramindo** adalah aplikasi berbasis web untuk mengelola surat masuk dan keluar secara digital.  
Dibangun menggunakan **Laravel 12**, dengan autentikasi API berbasis **Sanctum Token**, sistem ini memungkinkan **pengguna** dan **admin** untuk mencatat, mengarsipkan, mengunduh, serta memantau surat secara efisien dan terstruktur.

> 🚀 Dikembangkan oleh **Moammar Iqbal**   
> Proyek ini merupakan aplikasi berbasis web arsip surat PT. GRAMINDO.

---

## 🧩 Teknologi yang Digunakan

| Komponen | Teknologi |
|-----------|------------|
| **Framework** | Laravel 11 (PHP 8.3) |
| **Frontend View** | Blade + TailwindCSS |
| **Database** | MySQL / MariaDB |
| **Autentikasi API** | Laravel Sanctum |
| **Dokumentasi API** | OpenAPI 3.0 (`user.json` & `surat.json`) |
| **Penyimpanan File** | Storage Laravel (public disk) |
| **Server Requirement** | PHP ≥ 8.2, Composer, MySQL 5.7+, Node.js ≥ 18 |

---

## 📌 Fitur Utama

### 👤 Autentikasi & Manajemen User
- Login menggunakan email & password.
- Autentikasi API menggunakan **Bearer Token (Sanctum)**.
- Role-based access:
  - **Admin**: dapat mengelola user dan surat.
  - **User**: hanya dapat menambah & melihat surat miliknya.
- Logout otomatis dengan penghapusan token aktif.

### 📬 Manajemen Surat
- Input surat dengan dua mode:
  - **Upload File** (`PDF/DOCX/TXT/XLS/XLSX`) – disimpan di `storage/app/public/letters`
  - **Tautan Dokumen** (URL file online)
- Nomor surat **otomatis di-generate** berdasarkan:
  - Kode klasifikasi (contoh: `UMUM`, `KEU`, `DIR`, dll)
  - Tanggal surat (dalam format romawi bulan, contoh: `001/UMUM/XI/2025`)
- Validasi otomatis agar nomor urut tidak lompat (gapless sequence per bulan).
- Preview nomor surat berikutnya sebelum disimpan.
- Fitur **edit** dan **hapus** (khusus admin).
- Fitur **unduh file surat** sesuai nomor arsip.

### 📑 API & Dokumentasi
- API terbuka untuk integrasi internal.
- Dokumentasi lengkap dalam format OpenAPI (Swagger):

| File | Keterangan |
|------|-------------|
| `docs/api/user.json` | Endpoint login, profil, logout, dan manajemen user |
| `docs/api/surat.json` | Endpoint manajemen surat dan preview nomor otomatis |

---

## ⚙️ Instalasi & Konfigurasi

### 1️⃣ Clone Repository
```bash
git clone https://github.com/MoammarIqbal/arsip-surat
cd arsip-surat
```

### 2️⃣ Install Dependensi
```bash
composer install
npm install && npm run build
```

### 3️⃣ Konfigurasi `.env`
Salin file contoh:
```bash
cp .env.example .env
```

Edit nilai berikut sesuai environment:
```env
APP_NAME="Sistem Arsip Surat Gramindo"
APP_URL=http://sistem-arsip-gramindo.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arsip-surat
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public
```

### 4️⃣ Generate Key & Migrasi Database
```bash
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
```

### 5️⃣ Jalankan Server
```bash
php artisan serve
```
Akses di: [http://localhost:8000](http://localhost:8000)

---

## 🔐 Autentikasi API (Sanctum)

Setelah login (`POST /api/login`), sistem akan mengembalikan token:

```json
{
  "message": "Berhasil Login",
  "token": "1|masked_sanctum_token_here",
  "user": {
    "id": 1,
    "name": "Admin",
    "email": "admin@example.com",
    "role": "admin"
  }
}
```

Gunakan token ini untuk seluruh request API:
```
Authorization: Bearer <token>
```

---

## 📡 Endpoint API

| Metode | Endpoint | Deskripsi | Akses |
|---------|-----------|-----------|--------|
| POST | `/api/login` | Login pengguna | Public |
| GET | `/api/profile` | Profil user aktif | Semua user |
| POST | `/api/logout` | Logout dan hapus token | Semua user |
| GET | `/api/surat` | Daftar surat (paginate) | Semua user |
| GET | `/api/surat/{id}` | Detail surat | Semua user |
| POST | `/api/surat` | Tambah surat baru | Semua user |
| GET | `/api/surat/next-number` | Preview nomor otomatis | Semua user |
| GET | `/api/surat/{id}/download` | Unduh file surat | Semua user |
| PUT | `/api/surat/{id}` | Update surat | Admin |
| DELETE | `/api/surat/{id}` | Hapus surat | Admin |
| GET | `/api/users` | Daftar user | Admin |
| POST | `/api/users` | Tambah user baru | Admin |
| PUT | `/api/users/{id}` | Update user | Admin |
| DELETE | `/api/users/{id}` | Hapus user | Admin |

---

## 🧾 Dokumentasi OpenAPI

Tersedia dua file JSON yang bisa langsung di-preview di **Swagger UI** atau **VSCode Swagger Viewer**:

- [docs/api/user.json](public/openapi/user.json)
- [docs/api/surat.json](public/openapi/surat.json)

Preview lokal:
```bash
# Jalankan server Swagger UI sederhana
npx serve public/openapi
```
Lalu buka [http://localhost:3000/user.json](http://localhost:3000/user.json)

---

## 🧱 Arsitektur Sistem

```mermaid
flowchart TD
    A[User Interface (Blade)] -->|Request| B[API Routes]
    B --> C[AuthController / LetterController / UserManagementController]
    C --> D[(Database MySQL)]
    C --> E[(Storage Public)]
    E --> F[File Surat (PDF, DOCX, XLS)]
    D --> G[Table: users, letters, personal_access_tokens]
```

---

## 🚧 Rencana Pengembangan

✅ Versi 1.0 — Fitur inti (login, surat, user, upload, download)  

---

## 🧑‍💻 Kontributor

| Nama | Peran |
|------|--------|
| **Moammar Iqbal** | Developer, API Design |

---

## 🌐 Kontak

**Moammar Iqbal**  
📧 Email: ikbal30042005@gmail.com  
💼 LinkedIn: [linkedin.com/in/moammar-iqbal](https://www.linkedin.com/in/moammar-iqbal-388969376?utm_source=share_via&utm_content=profile&utm_medium=member_android)

---

> _"Digitalisasi bukan hanya soal kecepatan, tapi juga memastikan setiap dokumen memiliki jejak dan nilai arsip yang utuh."_ ✨

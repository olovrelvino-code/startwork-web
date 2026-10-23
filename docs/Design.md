# Design System & Panduan Frontend Komprehensif: Start Work Web Platform
**Multi-Role Web Application (Jobseeker, Employer, Admin Internal)**

Dokumen ini merupakan panduan spesifikasi arsitektur desain dan antarmuka (*design.md*) terpadu untuk platform **Start Work**, yang mengintegrasikan seluruh kebutuhan dari **Product Requirements Document (PRD)** serta tiga alur pengujian endpoint aktual:
1. **Alur Uji Endpoint Jobseeker** (`ALUR TEST ENDPOIN JOBSEEKER.md`)
2. **Alur Uji Endpoint Employer** (`ALUR TEST ENDPOIN EMPLOYER.md`)
3. **Alur Uji Endpoint Admin Internal** (`ALUR TEST ENDPOIN ADMIN INTERNAL.md`)

---

## 1. Ikhtisar Arsitektur & Prinsip Desain Terpadu

### 1.1 Tiga Pilar Pengguna & Karakteristik Visual
Platform Start Work melayani 3 aktor utama dengan pola interaksi dan kebutuhan antarmuka yang spesifik namun berada dalam satu ekosistem visual yang harmonis:

| Aktor | Domain & Konteks Pengguna | Karakteristik Visual & Pola UX | Pola Autentikasi & Sesi |
| :--- | :--- | :--- | :--- |
| **Jobseeker** (Pencari Kerja) | Publik & Portal Pribadi: Pencarian lowongan, eksplorasi perusahaan, pengisian data diri/CV, riwayat lamaran. | *Clean, consumer-friendly, high engagement, readable, card-based*, navigasi atas (*top navigation*), mobile-first responsive. | Registrasi publik `/register` (`role: jobseeker`), login modal / sesi cookie `/dashboard` & `/api/me`. |
| **Employer** (Perusahaan) | Workspace B2B / Dashboard Rekrutmen: Setup perusahaan, posting loker, seleksi pelamar, evaluasi CV privat, manajemen paket. | *Productivity-driven, metrics-oriented, focused, workflow stepper*, navigasi kombinasi sidebar / top-bar app header. | Registrasi khusus `/api/employer/register` (`role: employer`), login cookie, proteksi kepemilikan resource per-company. |
| **Admin Internal** (Super Admin) | Operational Back-Office CMS: Moderasi platform, posting atas nama perusahaan lain, monitoring pelamar, kurasi artikel, audit log. | *High-density data tables, minimal distraction*, inspirasi *simbarefinery.com*, strict ownership visual cues (*Admin Owned* vs *Employer Owned*). | Tidak ada registrasi publik (seed-only), login sesi admin `/login`, hak akses global dengan audit logging. |

### 1.2 Prinsip Desain Inti Sistem
1. **Unified Design Language with Role Specialization:** Warna dasar netral (Slate) dan brand blue (`#2563eb`) mengikat seluruh platform, namun variasi densitas disesuaikan: ruang napas lebih lega di Jobseeker, kerapatan informasi tinggi (*compact*) di Employer dan Admin CMS.
2. **State & Permission Transparency:** 
   - Lowongan internal (`apply_url: null`) vs lowongan eksternal (`apply_url: "https://..."`).
   - CV & Foto adalah data privat: tidak pernah disajikan melalui URL publik mentah, melainkan lewat controller endpoint terlindungi dengan otorisasi berbasis sesi.
3. **Resilient Feedback & Error Handling:** Penanganan kode status HTTP seragam di seluruh frontend:
   - `200 OK` / `201 Created`: Flash banner / toast sukses dengan konfirmasi instan.
   - `401 Unauthorized`: Redirect aman ke login sesi aktor terkait.
   - `404 Not Found`: Empty state terarah (misal: "Company belum memiliki lowongan aktif").
   - `409 Conflict`: Pesan pencegahan tegas (misal: "Sudah pernah melamar di lowongan ini", "Hapus lowongan gagal karena memiliki pelamar", atau "Aktivasi butuh subscription").
   - `419 CSRF Token Expired`: Modal auto-refresh sesi / prompt re-login tanpa kehilangan draf input form.
   - `422 Unprocessable Content`: Penandaan inline field validasi (misal: syarat data diri / CV wajib sebelum apply).
   - `501 Not Implemented`: Visual state informatif untuk modul yang ditunda PIC (payment gateway & trial subscription).

---

## 2. Fondasi Visual Terpadu (Design Tokens & Styles)

### 2.1 Palet Warna Global (Tailwind CSS Tokens)

```css
:root {
  /* Brand Primary - Professional Trust Navy & Electric Blue */
  --color-primary-50:  #eff6ff;
  --color-primary-100: #dbeafe;
  --color-primary-200: #bfdbfe;
  --color-primary-500: #3b82f6;
  --color-primary-600: #2563eb; /* Primary Action Button & Active Tabs */
  --color-primary-700: #1d4ed8;
  --color-primary-800: #1e40af;
  --color-primary-900: #0f172a; /* Admin/Employer Sidebar, Dark Accents */

  /* Neutral Backgrounds & Surfaces */
  --color-bg-canvas:   #f8fafc; /* Slate-50: Base background untuk app dashboard */
  --color-bg-public:   #ffffff; /* Pure white: Landing & blog reading experience */
  --color-surface:     #ffffff; /* Card, Modal, Table container */
  --color-surface-subtle: #f1f5f9; /* Slate-100: Input disabled & badge neutral */
  --color-border:      #e2e8f0; /* Slate-200: Garis batas standar */
  --color-border-focus:#93c5fd; /* Blue-300: Outline focus ring */

  /* Typography Colors */
  --color-text-main:   #0f172a; /* Slate-900: Heading & data penting */
  --color-text-body:   #334155; /* Slate-700: Paragraf & isi tabel */
  --color-text-muted:  #64748b; /* Slate-500: Subtitle, label, placeholder */
  --color-text-dim:    #94a3b8; /* Slate-400: Timestamps, disabled text */

  /* Semantic Application Status (Jobseeker, Employer, Admin) */
  --color-status-submitted-bg: #eff6ff; /* Blue-50 */
  --color-status-submitted-text: #1d4ed8; /* Blue-700: 'submitted' / baru masuk */
  --color-status-inprocess-bg: #fffbeb; /* Amber-50 */
  --color-status-inprocess-text: #b45309; /* Amber-700: 'in_process' / tahap review */
  --color-status-accepted-bg: #ecfdf5; /* Emerald-50 */
  --color-status-accepted-text: #047857; /* Emerald-700: 'accepted' / diterima */
  --color-status-rejected-bg: #fef2f2; /* Rose-50 */
  --color-status-rejected-text: #b91c1c; /* Rose-700: 'rejected' / tidak lolos */

  /* Semantic Job Post & Lifecycle Status */
  --color-job-active-bg:   #ecfdf5;
  --color-job-active-text: #065f46;
  --color-job-inactive-bg: #f1f5f9;
  --color-job-inactive-text:#475569;
  --color-job-expired-bg:  #fff1f2;
  --color-job-expired-text:#9f1239;
}
```

### 2.2 Tipografi & Skala Teks
- **Primary Typeface:** `Inter`, `system-ui`, `-apple-system`, `sans-serif`.
- **Monospace:** `ui-monospace`, `SFMono-Regular`, `Menlo`, `Monaco`, `Consolas` (digunakan untuk UUID ID, kode lowongan, dan hash status).
- **Hirarki:**
  - `Hero Heading`: `text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900`
  - `Page Heading`: `text-2xl font-bold tracking-tight text-slate-900`
  - `Card / Section Heading`: `text-lg font-semibold text-slate-800`
  - `Field Label & Column Header`: `text-xs font-semibold uppercase tracking-wider text-slate-500`
  - `Body Regular`: `text-sm font-normal text-slate-700 leading-relaxed`
  - `Caption & Helper Text`: `text-xs text-slate-500`

---

## 3. Spesifikasi Arsitektur Layout Shell

### 3.1 Shell Publik & Jobseeker (`layouts/jobseeker.blade.php`)
- **Top Navigation Bar (Sticky Header):**
  - Brand Logo `StartWork` + Tagline ringkas.
  - Menu Navigasi: `Cari Loker` (`/loker`), `Perusahaan` (`/perusahaan`), `Blog & Tips` (`/blog`), `Tentang Kami` (`/about-us`), `FAQs` (`/faqs`).
  - Sisi Kanan (State Dinamis via Alpine.js & Sesi):
    - *Jika Guest:* Tombol "Untuk Perusahaan" (`/employer/login`), tombol "Masuk" (buka Login Modal), tombol "Daftar" (`/register`).
    - *Jika Logged-in Jobseeker (`/api/me`):* Menu drop-down avatar inisial: Nama User, link `Profil & CV Saya` (`/profil`), `Lamaran Saya` (`/profil/lamaran`), dan tombol `Logout` (CSRF-protected POST).
- **Body Canvas:** Lebar konten terpusat `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8`.
- **Footer:** Links kategori populer, syarat ketentuan, copyright, dan link pendaftaran Employer.

### 3.2 Shell Portal Employer (`layouts/employer.blade.php`)
- **Struktur:** Minimalis, profesional, berorientasi produktivitas kerja dengan Header Top-bar + Subnav Tab atau Clean Left Sidebar:
  - Header: Logo Perusahaan aktif, Nama Perusahaan (Badge: `PT Madju Bangsa`), Sisa Kuota Posting Chip (`Kuota: 10 Loker`), Avatar Employer, Dropdown Akun.
  - Main Navigation:
    - `Dashboard` (`/employer/dashboard`): Statistik tayang & ringkasan pelamar baru.
    - `Kelola Loker` (`/employer/job-posts`): Daftar draf & posting aktif.
    - `Data Pelamar` (`/employer/applications`): Rekap pelamar masuk & filter per loker.
    - `Pengaturan` (`/employer/settings`): Profil pribadi, Perusahaan, & Keamanan.
  - Action Cepat: Tombol "+ Buat Lowongan Baru" (`/employer/job-posts/create`).

### 3.3 Shell Admin CMS Internal (`layouts/admin.blade.php`)
- **Struktur:** Side-Navigation gelap (`bg-slate-900`) + Top Utility Bar + Data-dense Work Canvas:
  - Sidebar:
    - Header: StartWork Admin Badge (`role: admin`).
    - Menu Inti: `Dashboard`, `Perusahaan` (Monitoring semua & Company Admin), `Lowongan Kerja` (Tab Admin vs All), `Pelamar & CV` (Akses lintas platform), `Artikel / News` (CMS Berita), `Paket & Promo` (Status Penundaan PIC).
  - Top Bar: Breadcrumb trail aktif, Pencarian Global ID, Badge Sesi Admin, Tombol Logout (`POST /logout`).

---

## 4. Rincian Antarmuka & Alur Per Role

### 4.1 Modul Jobseeker (Pencari Kerja)

#### A. Registrasi, Login & Onboarding Akun
- **Registrasi Akun Baru (`/register`):**
  - Form: Nama Lengkap, Alamat Email (`users.email`), Kata Sandi (min 8 karakter), Konfirmasi Kata Sandi, Nomor Telepon.
  - Sistem otomatis menetapkan `users.role = 'jobseeker'`.
  - Harapan HTTP: `302 Redirect` ke `/dashboard` dengan sesi aktif.
- **Login Jobseeker:**
  - Modal login 2 opsi: Input Email & Kata Sandi biasa, opsi Lupa Kata Sandi (`/forgot-password`), dan catatan bahwa Google OAuth sedang dalam fase rancangan (tidak boleh mengisi `email_verified_at` manual).

#### B. Profil Jobseeker, Skills Wajib & Manajemen CV (`/profil`)
Penting: Berdasarkan alur endpoint nyata, **Profil Utama, Skills (min 1), dan File CV adalah syarat mutlak (wajib)** sebelum dapat mengirim lamaran internal (`POST /api/job-posts/{id}/apply`).

- **Tab 1: Data Diri Utama (`POST/PUT /api/jobseeker/profile`):**
  - Foto Profil: Upload file lokal JPG/JPEG/PNG, maks 2 MB (`POST /api/jobseeker/profile/photo`). Bersifat opsional.
  - Nama Lengkap (`full_name`) — Wajib.
  - Domisili (`domicile` autocomplete kota) — Wajib.
  - Tanggal Lahir (`birth_date`, format YYYY-MM-DD via Flatpickr) — Wajib.
  - Jenis Kelamin (`gender`: Laki-laki / Perempuan) — Wajib.
  - Pendidikan Terakhir (`last_education`: SMA/SMK, D3, S1, S2, S3) — Wajib.
  - Link Portofolio (`portfolio_link`, URL) — Opsional.
- **Tab 2: Skills & Riwayat Pengalaman (`POST/PUT /api/jobseeker/profile/details`):**
  - *Catatan API:* Endpoint ini mengganti seluruh record (*complete replacement*). Form frontend harus mengirim kembali seluruh array yang ada saat disimpan.
  - **Skills (Wajib):** Tag input interaktif (minimal 1 keahlian, misal: `SQL`, `Laravel`, `Figma`).
  - **Riwayat Pekerjaan/Magang (Opsional):** Tipe pekerjaan (`Magang`/`Kerja`), Judul Posisi, Nama Perusahaan, Tanggal Mulai, Tanggal Selesai/Sekarang, Deskripsi Tanggung Jawab. Tombol hapus section memicu `DELETE /api/jobseeker/profile/details/work_experiences`.
  - **Pengalaman Organisasi (Opsional):** Nama Organisasi, Jabatan/Role, Periode.
  - **Riwayat Pendidikan (Opsional):** Jenjang, Institusi, Jurusan, Tahun Kelulusan.
  - **Sertifikasi (Opsional):** Nama Sertifikat, Penerbit.
- **Tab 3: Unggah Berkas CV (`POST /api/jobseeker/profile/cv`):**
  - Input file lokal (PDF/JPG/JPEG/PNG, maks 5 MB).
  - Tampilan status berkas: Menampilkan tanggal unggah `cv_uploaded_at` dan preview viewer terproteksi.
  - *Aturan Bisnis Endpoint:* Jika file CV fisik tidak ada di disk privat, sistem akan meminta re-upload sebelum mengizinkan apply.

#### C. Penjelajahan Lowongan & Alur Apply (`/loker` & `/loker/{id}`)
- **Halaman Daftar Lowongan (`/loker`):**
  - Search bar query (`?q=...`), Lokasi (`?location=...`), Kategori (`?category=...`), Tipe Kerja (`?work_type=Full-time/Part-time`), Sistem Kerja (`?job_type=On-site/Hybrid/Remote`).
  - Sorting: `latest`, `oldest`, `a-z`, `z-a`.
  - Pagination UI: Menggunakan query string `limit=15` dan `page=1, 2...` membaca total hitungan dari API.
  - Filter hanya menampilkan loker berstatus `active` yang belum kedaluwarsa (`expires_at > now`).
- **Halaman Detail Lowongan (`/loker/{id}`):**
  - Card Kiri: Logo perusahaan, Judul Lowongan, Nama Perusahaan, Lokasi, Deskripsi, Tanggung Jawab, Persyaratan, Skills Tag, Benefit.
  - Card Kanan (Sticky): Tipe Pekerjaan, Sistem Kerja, Rentang Gaji (atau "Gaji Dirahasiakan"), Tanggal Tayang.
  - **Logika Tombol Apply:**
    - **Kasus Lowongan Internal (`apply_url == null`):**
      - Tombol: "Kirim Lamaran Sekarang" (`POST /api/job-posts/{id}/apply`).
      - Validasi Frontend: Cek status profil lengkap & CV terunggah. Jika belum lengkap, trigger modal dialog penunjuk field yang kurang lengkap (mencegah error `422`).
      - Jika sudah pernah melamar: Tombol disabled dengan teks "Lamaran Sudah Terkirim" (mencegah error `409 Duplikat`).
    - **Kasus Lowongan Eksternal (`apply_url != null`):**
      - Tombol: "Lamar di Situs Perusahaan / Eksternal ↗".
      - Mengarahkan ke endpoint redirect `$base/api/job-posts/{id}/apply-now` (HTTP 302 menuju Google Form / Portal Karier perusahaan).

#### D. Tab "Lamaran Saya" (`/profil/lamaran`)
- Memanggil `GET /api/jobseeker/my-applications`.
- Tabel riwayat: Posisi Lowongan, Perusahaan, Tanggal Melamar, Status Terkini dengan Badge Warna:
  - `submitted`: Biru (Lamaran diterima sistem)
  - `in_process`: Kuning (Sedang ditinjau Employer)
  - `accepted`: Hijau (Kandidat Lolos / Diterima)
  - `rejected`: Merah (Tidak Lolos)
- Catatan: Jobseeker tidak dapat mengubah status lamaran miliknya atau melihat pelamar lain.

---

### 4.2 Modul Employer (Perusahaan)

#### A. Registrasi Employer & Setup Perusahaan
- **Registrasi Akun Employer (`POST /api/employer/register`):**
  - Form: Nama Lengkap (`full_name`), Nomor Telepon (`phone`), Email Perusahaan (`email`), Password (min 8 karakter), Konfirmasi Password.
  - Endpoint mengembalikan status `201 Created` dan otomatis membuat sesi login.
- **Company Setup & Profil (`/api/employer/company`):**
  - Pemilik otomatis diset ke `owner_user_id` dari sesi login.
  - Form: Nama Perusahaan (`name`), Lokasi Perusahaan (`location`), Website resmi (`website`), Deskripsi Perusahaan (`description`).
  - Tombol Buat Perusahaan hanya aktif jika `GET /api/employer/company` merespons `404`. Jika sudah ada (`200`), beralih ke form Update (`PUT`).
  - **Upload Logo Perusahaan (`POST /api/employer/company/logo`):**
    - File upload JPG/JPEG/PNG maks 2 MB. Tersimpan fisik di storage privat/publik Laravel.

#### B. Manajemen Posting Lowongan Kerja (`/employer/job-posts`)
- **Buat Draf Lowongan Baru (`POST /api/employer/job-posts`):**
  - Field: `title`, `category`, `location`, `job_type` (*Full-time, Part-time, Internship*), `work_type` (*On-site, Hybrid, Remote*), `salary_min`, `salary_max`, `description`, `responsibilities`, `requirements`, `skills`, `benefits`, `expires_at`.
  - Lowongan awal selalu tersimpan dengan status: `status = 'inactive'`.
  - Backend otomatis mengaitkan `company_id` dari profil perusahaan employer yang sedang login.
- **Edit & Update Draf (`PUT /api/employer/job-posts/{id}`):**
  - Memperbarui konten deskripsi dan persyaratan lowongan.
- **Hapus Lowongan (`DELETE /api/employer/job-posts/{id}`):**
  - *Aturan Bisnis:* Hanya draf tanpa pelamar yang dapat dihapus (`204 No Content`). Jika sudah ada pelamar, sistem memblokir dengan status `409 Conflict` demi menjaga integritas riwayat pelamar.

#### C. Penanganan Modul Subscription & Aktivasi Lowongan (Fase Penundaan PIC)
- **Status Aktivasi Lowongan (`PUT /api/employer/job-posts/{id}/status`):**
  - Berdasarkan addendum PRD & alur uji, fitur pembayaran otomatis/kuota berbayar sedang **ditunda oleh PIC**.
  - Upaya mengubah status menjadi `active` tanpa subscription aktif akan menghasilkan pesan: `409 Subscription aktif tidak ditemukan`.
  - **Desain UI:**
    - Tampilkan banner peringatan ramah: *"Fasilitas posting berbayar dan checkout paket sedang dalam persiapan teknis. Lowongan Anda tersimpan aman sebagai draf."*
    - Nonaktifkan tombol toggle "Publish" dengan tooltip informatif, atau sediakan tombol "Tutup Lowongan" (`status: inactive` + `inactive_reason`) bagi loker yang sebelumnya telah aktif.

#### D. Manajemen Pelamar Masuk & Evaluasi CV (`/employer/applications`)
- **Daftar Pelamar (`GET /api/employer/applications?limit=15&page=1`):**
  - Hanya menampilkan pelamar yang melamar ke lowongan milik perusahaan employer tersebut.
  - Kolom: ID Lamaran, Foto Pelamar, Nama Pelamar, Posisi yang Dilamar, Tanggal Melamar, Status Lamaran.
- **Detail Pelamar & Download CV Terproteksi (`/cv`):**
  - Menampilkan profil lengkap, pendidikan, riwayat kerja, dan keahlian pelamar.
  - Tombol Unduh CV: Mengunduh via endpoint privat `$base/api/employer/applications/{id}/cv` dengan stream file asli (PDF/JPG) bertanda otorisasi sesi.
- **Perubahan Status Seleksi Pelamar (`PUT /api/employer/applications/{id}/status`):**
  - Alur Status yang Diizinkan:
    1. Awal: `submitted`
    2. Review: `in_process`
    3. Keputusan Akhir: `accepted` (Diterima) atau `rejected` (Ditolak).
  - Status akhir bersifat permanen dan tidak dapat diubah kembali.

---

### 4.3 Modul Admin Internal (Web CMS)

#### A. Autentikasi Admin & Kebijakan Kepemilikan Data
- Login khusus melalui `$base/login` dengan akun demo terdaftar (tidak ada pendaftaran publik).
- **Aturan Pemisahan Kepemilikan Data (*Ownership Isolation*):**
  - Perusahaan yang dibuat Admin (`POST /api/admin/companies`) dimiliki penuh oleh admin tersebut (`owner_user_id == me.id`). Admin dapat membuat beberapa company.
  - Perusahaan yang dibuat Employer (`/api/employer/company`) tetap menjadi milik employer bersangkutan. Admin memiliki akses *monitoring global* untuk seluruh data, namun hak `UPDATE`/`DELETE` company dibatasi khusus untuk company milik admin.

#### B. Monitoring Perusahaan & Buat Company Admin (`/admin/companies`)
- **Tabel Monitoring Terpadu:**
  - Menampilkan semua perusahaan (Employer & Admin).
  - Pencarian `?q={query}` (debounce 300ms).
  - Badge Status Kepemilikan: `Admin Owned` (Badge Indigo) vs `Employer Owned` (Badge Slate).
  - Aksi: Tombol Edit/Hapus hanya muncul pada company milik admin. Company employer hanya dapat dilihat detail dan lowongannya.
- **Drawer / Modal Tambah Company Admin:**
  - Field: Nama Perusahaan, Lokasi, Website, Deskripsi.
  - Upload logo file lokal via `POST /api/admin/companies/{id}/logo` (maks 2 MB).

#### C. Manajemen Lowongan Kerja Admin (`/admin/job-posts`)
- **Posting Loker Admin (`POST /api/admin/job-posts`):**
  - Admin memilih perusahaan miliknya dari dropdown `company_id`.
  - Bebas kuota paket (`subscription_id` dikosongkan secara otomatis).
  - Dukungan kolom `apply_url`:
    - Jika diisi tautan: Menjadi lowongan pendaftaran eksternal.
    - Jika dikosongkan: Menjadi lowongan internal yang siap menerima lamaran Jobseeker.
  - Status default: Langsung `active` (selama `expires_at` belum lewat).
- **Ubah Status Loker (`PUT /api/admin/job-posts/{id}/status`):**
  - Nonaktifkan loker dengan menyertakan `status: inactive` dan alasan `inactive_reason`.
  - Fitur scheduler otomatis: Command `job-posts:expire` otomatis menandai loker kadaluwarsa sebagai `expired`.
- **Hapus Loker Admin:**
  - Hanya dapat diproses jika status `inactive`/`expired` dan jumlah pelamar 0 (`DELETE /api/admin/job-posts/{id}`).

#### D. Monitoring Pelamar Global & Inspeksi CV (`/admin/applicants`)
- Admin dapat memantau seluruh pelamar di platform lintas perusahaan dan lintas lowongan.
- Filter Bar Multi-Kriteria:
  - Pencarian Nama Kandidat (`?name=...`)
  - Filter Perusahaan (`?company_id=...`)
  - Kontrol Pagination (`?limit=15&page=...`).
- Download CV Aman via Admin Endpoint:
  - Stream biner berkas CV privat melalui `$base/api/admin/applicants/{id}/cv`.

#### E. CMS Artikel & Berita (`/admin/articles`)
- Manajemen artikel edukasi karier yang tayang di halaman Blog publik.
- Form CRUD Artikel: Judul (`title`), Konten teks / markdown (`content`), Cover gambar opsional (maks 2 MB), Status radio button (`draft` / `published`).
- Aturan Hak Akses: Artikel `draft` hanya muncul di tabel admin; artikel `published` otomatis tampil di halaman Blog publik Jobseeker.

#### F. Paket & Subscription (Status Penundaan PIC)
- Halaman katalog referensi:
  - **Basic:** Rp 99.000 / Kuota 10 loker / 15 hari.
  - **Standard:** Rp 149.000 / Kuota 20 loker / 30 hari.
  - **Premium:** Rp 239.000 / Kuota 30 loker / 45 hari.
- Notice Banner Penundaan: *"Endpoint /grant-trial mengembalikan 501 Not Implemented sesuai keputusan penundaan PIC."* Tombol aksi grant-trial dinonaktifkan secara anggun.

---

## 5. Pola Komponen & State UX Terstandarisasi

### 5.1 Status Badges Universal
Komponen badge Tailwind CSS yang konsisten di ketiga modul:

```html
<!-- Status Lamaran: Submitted (Baru Masuk) -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
  <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-blue-500"></span> Terkirim
</span>

<!-- Status Lamaran: In Process (Sedang Direview) -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
  <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-amber-500"></span> Diproses
</span>

<!-- Status Lamaran: Accepted (Diterima) / Loker: Active -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
  <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span> Diterima / Aktif
</span>

<!-- Status Lamaran: Rejected (Ditolak) / Loker: Expired -->
<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
  <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-rose-500"></span> Ditolak / Expired
</span>

<!-- Tag Kepemilikan Admin (Khusus Admin CMS) -->
<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
  Admin Owned
</span>
```

### 5.2 Pola Dialog Konfirmasi Aman (Destructive Actions)
Untuk aksi penghapusan (hapus lowongan, hapus company, nonaktifkan posting):
- Modal konfirmasi menampilkan ringkasan data yang akan dihapus.
- Pengecekan pra-syarat otomatis di sisi klien sebelum memanggil API (misal: "Lowongan ini memiliki 3 pelamar aktif. Lowongan dengan riwayat pelamar tidak dapat dihapus untuk melindungi data pelamar").
- State loading pada tombol konfirmasi (`Submitting...`) untuk mencegah double-click / race condition.

### 5.3 Pola Upload Berkas Lokal Terproteksi
Komponen drop-zone untuk Logo Perusahaan, Foto Profil, dan Berkas CV:
- Validasi tipe MIME di sisi browser:
  - Foto/Logo: `.jpg`, `.jpeg`, `.png` (maks 2 MB).
  - CV: `.pdf`, `.jpg`, `.jpeg`, `.png` (maks 5 MB).
- Indikator progres upload dan preview thumbnail instan sebelum disimpan ke database.

---

## 6. Matriks Rute & Integrasi Endpoint Seluruh Role

Berikut tabel referensi silang lengkap antara antarmuka Blade, URL rute frontend, endpoint backend Laravel, dan peran yang berwenang:

| Modul Role | URL Tampilan Blade | Method & Endpoint Backend API | Keterangan & Validasi Kunci |
| :--- | :--- | :--- | :--- |
| **Umum** | `/login` | `POST /login` | Autentikasi sesi multi-role, refresh CSRF. |
| **Umum** | `/logout` | `POST /logout` | Invalidasi cookie sesi, redirect ke halaman login. |
| **Umum** | Header Sesi | `GET /api/me` | Membaca data identitas dan role user login aktif. |
| **Jobseeker** | `/register` | `POST /register` | Registrasi pelamar baru (`role: jobseeker`). |
| **Jobseeker** | `/loker` | `GET /api/job-posts` | Filter query: `q`, `category`, `location`, `work_type`, `job_type`, `sort`, `page`, `limit`. |
| **Jobseeker** | `/loker/{id}` | `GET /api/job-posts/{id}` | Detail lowongan publik. |
| **Jobseeker** | `/loker/{id}` (Eksternal) | `GET /api/job-posts/{id}/apply-now`| Redirect 302 menuju `apply_url`. |
| **Jobseeker** | `/profil` | `POST/PUT /api/jobseeker/profile` | Simpan data diri profil utama. |
| **Jobseeker** | `/profil` | `POST /api/jobseeker/profile/photo`| Upload berkas foto profil (maks 2MB). |
| **Jobseeker** | `/profil` | `POST /api/jobseeker/profile/cv` | Upload berkas CV wajib (maks 5MB). |
| **Jobseeker** | `/profil` | `POST/PUT /api/jobseeker/profile/details` | Mengganti seluruh daftar skills & riwayat. |
| **Jobseeker** | `/loker/{id}` (Internal) | `POST /api/job-posts/{id}/apply` | Kirim lamaran internal (butuh profil + skills + CV). |
| **Jobseeker** | `/profil/lamaran` | `GET /api/jobseeker/my-applications`| Daftar riwayat lamaran milik pelamar. |
| **Employer** | `/employer/register` | `POST /api/employer/register` | Pendaftaran akun employer baru (`role: employer`). |
| **Employer** | `/employer/company` | `GET/POST/PUT /api/employer/company` | Setup dan update profil perusahaan milik employer. |
| **Employer** | `/employer/company/logo`| `POST /api/employer/company/logo`| Upload logo resmi perusahaan (maks 2MB). |
| **Employer** | `/employer/job-posts` | `GET/POST /api/employer/job-posts` | Buat lowongan baru (status default: `inactive`). |
| **Employer** | `/employer/job-posts/{id}`| `PUT/DELETE /api/employer/job-posts/{id}` | Edit konten loker atau hapus draf tanpa pelamar. |
| **Employer** | Status Loker | `PUT /api/employer/job-posts/{id}/status` | Tutup loker (`status: inactive` + reason). |
| **Employer** | `/employer/applications`| `GET /api/employer/applications` | Daftar pelamar loker perusahaan (paginated). |
| **Employer** | `/employer/applications/{id}/cv` | `GET /api/employer/applications/{id}/cv` | Unduh stream berkas fisik CV pelamar. |
| **Employer** | Status Pelamar | `PUT /api/employer/applications/{id}/status` | Update status: `in_process`, `accepted`, `rejected`. |
| **Admin** | `/admin/companies` | `GET/POST /api/admin/companies` | Monitor semua company & buat company admin baru. |
| **Admin** | `/admin/companies/{id}` | `PUT/DELETE /api/admin/companies/{id}` | Kelola profil company milik admin. |
| **Admin** | `/admin/companies/{id}/logo` | `POST /api/admin/companies/{id}/logo` | Upload logo company milik admin. |
| **Admin** | `/admin/job-posts` | `GET/POST /api/admin/job-posts` | Monitor loker global & posting atas nama admin. |
| **Admin** | `/admin/job-posts/{id}/status` | `PUT /api/admin/job-posts/{id}/status` | Toggle status loker `active` / `inactive`. |
| **Admin** | `/admin/applicants` | `GET /api/admin/applicants` | Monitoring seluruh pelamar lintas perusahaan. |
| **Admin** | `/admin/applicants/{id}/cv` | `GET /api/admin/applicants/{id}/cv` | Download CV pelamar dengan audit otorisasi. |
| **Admin** | `/admin/articles` | `GET/POST/PUT/DELETE /api/admin/articles` | CRUD artikel/news karier dan status draft/publish. |
| **Admin** | `/admin/packages` | `GET /api/packages` | Katalog acuan paket (Fitur payment/trial ditunda). |

---

## 7. Panduan Implementasi Frontend & Eksekusi Blade

1. **Struktur Direktori Blade Rekomendasi:**
   ```text
   resources/views/
   ├── layouts/
   │   ├── jobseeker.blade.php  # Public & Jobseeker Master Layout
   │   ├── employer.blade.php   # Employer Portal Layout
   │   └── admin.blade.php      # Admin CMS High-Density Layout
   ├── components/
   │   ├── status-badge.blade.php
   │   ├── modal-confirm.blade.php
   │   ├── data-table.blade.php
   │   ├── pagination.blade.php
   │   └── file-upload.blade.php
   ├── jobseeker/
   │   ├── loker/               # Index & Detail Lowongan
   │   ├── company/             # Daftar & Detail Perusahaan
   │   └── profile/             # Data Diri, Upload CV, & Lamaran Saya
   ├── employer/
   │   ├── dashboard/           # Metrik & Ringkasan Loker
   │   ├── job-posts/           # CRUD Loker Employer
   │   ├── applications/        # Pelamar & Download CV
   │   └── settings/            # Akun & Perusahaan
   └── admin/
       ├── companies/           # Monitoring & Company Admin
       ├── job-posts/           # Lowongan Global & Buat Loker
       ├── applicants/          # Monitoring Pelamar & CV
       └── articles/            # CMS Artikel Berita
   ```
2. **Ketergantungan State Ringan (Alpine.js):**
   - Gunakan `x-data` untuk interaktivitas lokal: toggle filter drawer, tab switcher pada profil dan settings, dynamic tag inputs (skills & benefits), serta modal alert.
   - Hindari membuat Single Page Application (SPA) rumit; manfaatkan perenderan server-side Blade yang ramah SEO untuk halaman publik loker dan artikel blog.
3. **Standar Keamanan Form:**
   - Semua form POST/PUT/DELETE wajib menyertakan direktif `@csrf` Laravel untuk mencegah error `419 CSRF Token Expired`.
   - File input selalu menggunakan atribut `enctype="multipart/form-data"`.

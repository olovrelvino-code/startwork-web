PRODUCT REQUIREMENTS DOCUMENT

START WORK WEBSITE

Platform Lowongan Kerja — Migrasi Squarespace ke Web Native

Nama Proyek: Start Work Website

Jenis Proyek: Migrasi website Squarespace → web native (berbasis kode)

Role: Jobseeker, Employer, Admin Internal (baru)

Tanggal Dokumen: 12 Oktober 2026

Cakupan PRD: Role Jobseeker, Employer, dan Admin Internal

Status: Fase Analisis & Perencanaan

DAFTAR ISI

1.  Gambaran Umum Proyek

2.  Aktor & Peran

3.  Ketentuan & Aturan Bisnis

4.  Fitur Lengkap Per Aktor

5.  Struktur Halaman & Navigasi

6.  Hal yang Belum Ditentukan

7.  Use Case Diagram

8.  Activity Diagram

9.  Sequence Diagram

10.  Class Diagram

11.  Flowchart Alur Utama

# 1. Gambaran Umum Proyek

Start Work adalah platform lowongan kerja yang mempertemukan pencari kerja (Jobseeker) dengan perusahaan (Employer). Proyek ini bertujuan memigrasikan website Start Work dari Squarespace ke web native yang dibangun menggunakan kode, sekaligus menambahkan satu role baru yaitu Admin Internal.

Tiga hal yang menjadi fokus: (1) mereplikasi fitur Jobseeker dan Employer, (2) membangun role Admin Internal sebagai web CMS termasuk Kelola Loker, package dan langganan, monitoring lowongan/pelamar, serta pengelolaan artikel, dan (3) menyediakan dokumen acuan untuk memantau progres pengembangan.

| Parameter | Keterangan |
| --- | --- |
| Nama Sistem | Start Work Website |
| Jenis Proyek | Migrasi Squarespace → web native (kode) |
| Jumlah Role | 3 (Jobseeker, Employer, Admin Internal) |
| Role Baru | Admin Internal (web CMS) |
| Referensi Tampilan Admin | simbarefinery.com (login sebagai admin), diberikan oleh PIC |
| Halaman Publik | Landing, Loker, Perusahaan, About Us, FAQs, Blog |
| Area Employer | Login, Registrasi, Company Setup, Dashboard, Post Job, Settings |
| Area Admin Internal | Post Job atas nama perusahaan, Artikel/News, Kelola Loker, package, langganan, perusahaan, lowongan, pelamar |
| Jenis Sistem | Web-based |
| Framework | Laravel (PHP) dengan Blade |
| Database | MySQL/MariaDB bawaan Laragon |
| Lingkungan Pengembangan | Laragon |
| Status | Fase Analisis & Perencanaan |

## 1.1 Teknologi yang Digunakan

Rencana tim pengembang untuk membangun Start Work sebagai web native menggunakan Laravel dengan Blade dan Laragon sebagai lingkungan pengembangan. Aplikasi dibangun sebagai satu aplikasi (monolit): halaman dirender di server, tanpa API terpisah. Alur datanya: Browser → route Laravel → Controller → Eloquent → MySQL, lalu respons berupa halaman Blade.

| Layer | Teknologi | Peran dalam Proyek |
| --- | --- | --- |
| Framework | Laravel (PHP) | Routing, controller, autentikasi, otorisasi per role, validasi, ORM Eloquent, migration, dan logika bisnis (lamaran, kuota, posting loker) |
| Tampilan | Blade | Template server-side untuk seluruh halaman tiga role: halaman publik Jobseeker, profil dan lamaran Jobseeker, dashboard Employer, dan (nanti) panel Admin. Layout dan komponen Blade dipakai ulang |
| Database | MySQL/MariaDB (bawaan Laragon) | Penyimpanan data User, Company, Job, Application, dan Article. Foto, CV, dan logo disimpan melalui storage Laravel |
| Lingkungan pengembangan | Laragon | Paket lokal Windows berisi web server, PHP, MySQL, Composer, dan Node.js; virtual host otomatis (misalnya startwork.test) |

Basis data yang digunakan adalah MySQL/MariaDB karena sudah termasuk dalam Laragon dan didukung penuh oleh Laravel. Supabase dan React tidak lagi digunakan pada rencana ini.

## 1.2 Alasan Pemilihan Teknologi

| Teknologi | Alasan Pemilihan | Batasan yang Perlu Diperhatikan |
| --- | --- | --- |
| Laravel + Blade | Satu codebase dan satu bahasa utama (PHP) sehingga lebih cepat dikembangkan dan dirawat oleh tim kecil. Autentikasi, role, validasi, upload file, email, dan antrean sudah tersedia di dalam Laravel. Komponen Blade dapat dipakai ulang untuk card loker, card perusahaan, card artikel, dan form Post Job. Halaman dirender di server sehingga ramah SEO untuk halaman Loker, Job Detail, Perusahaan, dan Blog, yang penting setelah migrasi dari Squarespace. Tidak perlu membangun dan menjaga API terpisah. | Interaksi dinamis (dropdown dengan search, date picker, entri berulang pada data diri, stepper, tab) memerlukan JavaScript tambahan seperti Alpine.js atau Livewire. Bila kelak dibutuhkan aplikasi mobile atau frontend terpisah, API perlu ditambahkan. |
| Laragon | Setup lokal di Windows sangat cepat: web server, PHP, MySQL, Composer, dan Node.js dalam satu paket, serta virtual host otomatis. Ringan dan cocok untuk pengembangan harian. | Hanya untuk pengembangan lokal dan berjalan di Windows. Hosting produksi (umumnya Linux) perlu dipilih dan versi PHP/MySQL harus disamakan. Anggota tim non-Windows memerlukan alternatif seperti Laravel Sail (Docker). |
| MySQL/MariaDB | Sudah termasuk dalam Laragon dan didukung penuh oleh Eloquent dan migration. Cocok untuk data relasional Start Work: User, Company, Job, Application, dan Article, serta query filter dan sort loker. | Backup dan hosting database dikelola sendiri (tidak ada dashboard terkelola). Class Diagram memakai UUID; MySQL mendukungnya (kolom char 36 atau binary), atau dapat diganti ID auto-increment sesuai keputusan tim. |

## 1.3 Evaluasi Efisiensi untuk Tiga Role

Penilaian di bawah menguji apakah Laravel + Blade dapat memenuhi kebutuhan masing-masing role yang tercantum pada dokumen sumber, yaitu Jobseeker, Employer, dan Admin Internal.

| Role | Kebutuhan Utama | Dukungan Stack | Kecocokan |
| --- | --- | --- | --- |
| Jobseeker | Halaman publik yang banyak dibaca (search, filter, sort, Job Detail, Blog, SEO), ditambah akun Jobseeker: registrasi/login, data diri, upload CV, apply lowongan, dan daftar lamaran | Blade menghasilkan HTML di server (SEO baik); filter dan sort lewat query string dengan paginator bawaan; Laravel menangani autentikasi, validasi file, dan penyimpanan CV; Alpine.js untuk dropdown search dan entri berulang | Cocok. Form data diri yang panjang dengan entri berulang (pengalaman, pendidikan, skills) memerlukan JavaScript ringan atau Livewire. Card Contact yang sticky cukup dengan CSS |
| Employer | Registrasi, login (email dan Google), Stepper Company Setup, Dashboard KPI (termasuk Sisa Kuota), Post Job, Settings tiga tab, daftar pelamar dan CV pelamar | Laravel: autentikasi, validasi form, upload logo, dan policy agar Employer hanya mengelola loker, perusahaan, dan pelamar miliknya; Blade: layout dashboard, stepper, dan tab; Eloquent: relasi Job dan Application | Cocok. Kuota mengikuti package yang dibeli (Basic 10, Standard 20, Premium 30 loker); gateway pembayaran perlu ditentukan (lihat Bagian 6) |
| Admin Internal (baru) | CMS Admin Internal: kelola package/langganan, monitoring perusahaan, lowongan, pelamar, posting atas nama perusahaan, artikel/news, dan akses data diri/CV Jobseeker dengan hak akses ketat | Laravel middleware/Policy untuk admin dan kontrol data; Blade untuk halaman Kelola Loker, monitoring, dan artikel; audit log untuk aksi admin | Cocok. Perlu memilih editor artikel (rich text). Bila waktu terbatas, panel admin dapat dipercepat dengan Filament (berbasis Livewire), dengan konsekuensi tampilan berbeda dari halaman lain |

## 1.4 Pembagian Tanggung Jawab di Dalam Aplikasi

Karena seluruh aplikasi berada dalam satu codebase Laravel, pembagian berikut menjaga struktur tetap rapi dan konsisten.

| Aspek | Dikelola Oleh | Alasan |
| --- | --- | --- |
| Autentikasi (registrasi, login, lupa kata sandi, login Google) | Laravel Auth (starter kit Breeze dengan Blade) dan Socialite | Satu sistem autentikasi berbasis sesi untuk tiga role, tanpa token terpisah |
| Otorisasi per role (Jobseeker, Employer, Admin Internal) | Laravel (middleware role, Gate, dan Policy) | Aturan seperti "Employer hanya mengelola loker miliknya" dan "hanya Admin yang dapat posting atas nama perusahaan" berada di satu tempat |
| Tampilan | Blade: layout publik, layout dashboard Employer, dan layout Admin, dengan komponen yang dipakai ulang | Konsistensi tampilan dan pengurangan duplikasi |
| Akses data | Controller dengan model Eloquent | Relasi User, Company, Job, Application, dan Article ditangani ORM |
| Skema database | Migration Laravel pada MySQL | Struktur tabel tersimpan di repository dan dapat dilacak versinya |
| File (logo perusahaan, foto artikel, foto profil, CV, sertifikat) | Laravel Storage: disk privat untuk foto profil, CV, dan sertifikat; disk publik untuk logo dan foto artikel | CV dan foto memuat data pribadi |
| Akses CV dan data diri Jobseeker | Controller dengan Policy Laravel | Hanya pemilik akun, Employer, dan Admin Internal yang dapat melihat. Employer hanya untuk pelamar pada loker miliknya; file CV diunduh lewat controller yang memeriksa hak akses, bukan URL publik |
| Lamaran (Application) | Policy Laravel | Jobseeker hanya melihat lamarannya sendiri; Employer hanya melihat lamaran pada loker miliknya |
| Validasi data | Form Request Laravel (wajib) dan atribut HTML untuk umpan balik cepat | Validasi server tetap menjadi acuan keamanan |
| Interaktivitas halaman | Alpine.js; Livewire bila form atau filter sangat dinamis | Menambah interaksi tanpa menjadikan aplikasi SPA |

## 1.5 Kesimpulan Efisiensi dan Risiko

Laravel + Blade + Laragon dapat memenuhi seluruh kebutuhan Jobseeker, Employer, dan Admin Internal, dan merupakan pilihan yang ramping untuk aplikasi seukuran ini: satu codebase, satu bahasa utama, tanpa API terpisah, dan SEO baik karena halaman dirender di server. Kebutuhan Start Work didominasi operasi CRUD, autentikasi, dan pembatasan akses berbasis role, yang memang menjadi keunggulan Laravel. Kompromi utamanya adalah interaksi antarmuka yang lebih kaya (form data diri berulang, dropdown dengan search, stepper) membutuhkan JavaScript tambahan, serta Laragon hanya untuk pengembangan lokal sehingga hosting produksi perlu diputuskan.

Bila kebutuhan interaksi meningkat, jalur pengembangannya: (a) tambahkan Livewire tanpa meninggalkan Blade, atau (b) migrasi bertahap ke Inertia.js dengan React atau Vue pada bagian tertentu.

| # | Risiko | Dampak | Mitigasi |
| --- | --- | --- | --- |
| 1 | Interaksi UI kompleks pada form data diri (entri berulang, dropdown search, date picker) | Pengerjaan lebih lama atau pengalaman pengguna kurang halus | Gunakan Alpine.js dan pustaka ringan (Flatpickr, Tom Select); pertimbangkan Livewire untuk form tertentu |
| 2 | Perbedaan lingkungan Laragon (Windows) dan produksi (umumnya Linux) | Bug yang hanya muncul di produksi, misalnya perbedaan huruf besar-kecil pada nama file | Samakan versi PHP dan MySQL, uji di staging, dan kelola konfigurasi lewat file .env |
| 3 | Keamanan data pribadi (CV, foto profil, sertifikat) | Kebocoran data bila file dapat diakses publik | Simpan di disk privat, unduh lewat controller dengan Policy, validasi tipe dan ukuran file |
| 4 | SEO setelah migrasi dari Squarespace | Trafik turun bila URL dan meta tidak dijaga | Gunakan URL ramah SEO, meta tag per halaman, sitemap, dan redirect dari URL lama |
| 5 | Hosting produksi belum diputuskan | Jadwal rilis tertunda | Tentukan hosting PHP dan database sejak awal (Bagian 6) dan siapkan staging |

## 1.6 Saran Framework dan Library Pendukung

Untuk seluruh role (Jobseeker, Employer, dan Admin Internal, termasuk alur lamaran, package, dan pembayaran), Laravel dengan Blade sudah cukup dan tidak diperlukan framework tambahan. Fitur lamaran dan langganan package adalah operasi relasional dengan aturan akses yang sudah tercakup oleh Eloquent, Policy, Form Request, dan Queue. Audit log Admin dapat memakai paket seperti spatie/laravel-activitylog, dan pembayaran memakai SDK gateway yang dipilih (gateway belum ditentukan). Berikut library pendukung yang disarankan.

| # | Kebutuhan | Saran | Alasan |
| --- | --- | --- | --- |
| 1 | Autentikasi dan scaffolding | Laravel Breeze (stack Blade) | Menyediakan registrasi, login, lupa kata sandi, dan verifikasi email; dapat disesuaikan untuk tiga role |
| 2 | Login Google | Laravel Socialite | Dibutuhkan Employer dan dapat dipakai Jobseeker |
| 3 | Otorisasi | Gate dan Policy bawaan (Spatie Laravel-Permission hanya jika role bertambah banyak) | Aturan inti: Employer hanya melihat pelamar loker miliknya, Jobseeker hanya melihat lamarannya sendiri |
| 4 | Validasi | Form Request Laravel | Aturan field data diri, format dan ukuran file (foto 2 MB, CV 5 MB) |
| 5 | Interaktivitas | Alpine.js; Livewire bila form atau filter perlu sangat dinamis | Dropdown search, tab, stepper, dan entri berulang tanpa membangun SPA |
| 6 | Styling | Tailwind CSS (bawaan Breeze) melalui Vite, atau Bootstrap | Layout kompleks: grid dinamis, card, dan tab |
| 7 | Date picker dan dropdown dengan search | Flatpickr dan Tom Select (atau Choices.js) | Memenuhi kebutuhan field tanggal lahir dan lokasi domisili |
| 8 | Upload dan pengolahan file | Laravel Storage (disk privat); Intervention Image (opsional) untuk mengecilkan foto | CV dan foto memuat data pribadi |
| 9 | Filter, sort, dan pagination loker | Query scope Eloquent dan paginator bawaan | Memenuhi filter Category, Job Type, Work Type, Date Posted, dan sort |
| 10 | Notifikasi pelamar baru (jika diputuskan) | Laravel Notification/Mail dengan Queue (driver database) | Proses apply tidak menunggu pengiriman email |
| 11 | SEO | Meta tag per halaman di Blade; paket sitemap (misalnya spatie/laravel-sitemap) | Menjaga trafik setelah migrasi |
| 12 | Pengujian | Pest atau PHPUnit; Laravel Dusk (opsional) untuk uji browser | Alur apply dan akses pelamar perlu diuji otomatis |
| 13 | Lingkungan dan versi | Laragon, Git, dan Composer | Lingkungan lokal seragam dan manajemen dependensi |

Filament (panel admin berbasis Livewire) dapat dipertimbangkan untuk mempercepat pembangunan CMS Admin Internal, dengan konsekuensi tampilan berbeda dari halaman lain.

# 2. Aktor & Peran

Sistem melibatkan tiga aktor utama dengan peran dan hak akses yang berbeda.

Jobseeker

Pencari kerja yang mengakses website publik untuk mencari lowongan, melihat perusahaan, serta membaca FAQs dan artikel blog. Jobseeker dapat registrasi dan login, mengisi data diri, mengunggah CV, melamar lowongan langsung melalui platform, serta melihat daftar lamarannya pada profil.

Employer

Perwakilan perusahaan yang mendaftar, melengkapi profil perusahaan, dan memposting lowongan sendiri melalui dashboard Employer. Employer juga melihat pelamar yang melamar ke loker miliknya beserta data diri dan CV-nya.

Admin Internal (Role Baru)

Pengelola internal Start Work yang bekerja melalui tampilan web CMS. Mengelola package dan langganan, memonitor perusahaan, lowongan, pelamar/jobseeker, memposting lowongan atas nama perusahaan lain, membuat artikel/news, serta melihat data diri dan CV Jobseeker sesuai hak akses.

| Aktor | Area Akses | Task Utama |
| --- | --- | --- |
| Jobseeker | Website publik + akun Jobseeker | Cari & lihat loker, lihat perusahaan, baca blog/FAQs, registrasi/login, isi data diri, upload CV, apply lowongan, lihat lamaran saya |
| Employer | Dashboard Employer | Registrasi, setup perusahaan, post job, kelola akun, lihat pelamar dan CV pelamar |
| Admin Internal | Panel CMS Admin | Kelola Loker (package, langganan, perusahaan, lowongan, pelamar), post job atas nama perusahaan lain, kelola artikel, lihat data diri/CV Jobseeker sesuai hak akses |

# 3. Ketentuan & Aturan Bisnis

## 3.1 Aturan Formulir Employer

| # | Formulir | Field | Ketentuan |
| --- | --- | --- | --- |
| 1 | Registrasi | Nama lengkap, Nomor Telepon, Email perusahaan | Data Diri |
| 2 | Registrasi | Kata sandi, Konfirmasi kata sandi | Kata sandi minimal 8 karakter |
| 3 | Company Setup | Nama Perusahaan, Lokasi Perusahaan | Wajib |
| 4 | Company Setup | Logo Perusahaan, Website, URL Kontak | Opsional |
| 5 | Post Job | Judul Posisi, Kategori, Lokasi, Tipe Pekerjaan, Sistem Kerja | Informasi dasar |
| 6 | Post Job | Gaji minimum, Gaji maksimum | Opsional |
| 7 | Post Job | Deskripsi Pekerjaan, Tanggung Jawab, Persyaratan, Keahlian, Benefit | Deskripsi & persyaratan |
| 8 | Settings – Perusahaan | Website, Deskripsi Perusahaan | Opsional |
| 9 | Settings – Keamanan | Kata sandi saat ini, baru, konfirmasi baru | Ganti kata sandi |
| 10 | Post Job | Package dan kuota | Harus memiliki package berbayar aktif dan sisa kuota sebelum loker dapat dipublikasikan; setiap loker yang berhasil diposting memakai 1 kuota. |

## 3.2 Aturan Tampilan Loker & Perusahaan (Jobseeker)

| # | Elemen | Ketentuan |
| --- | --- | --- |
| 1 | Landing – Pencarian | Tombol "Cari Lowongan" mengarahkan ke halaman Loker |
| 2 | Landing – Lowongan Terbaru | Menampilkan preview beberapa card lowongan dari perusahaan yang membuka lowongan; klik card langsung membuka Job Detail perusahaan tersebut. Tombol "Lihat Semua Lowongan Kerja" menuju halaman Loker |
| 3 | Filter Loker | Category, Job Type, Work Type, Date Posted (sidebar kiri) |
| 4 | Sort Loker | Latest / Oldest / A-Z / Z-A, serta pengaturan jumlah kolom |
| 5 | Open Positions Perusahaan | Grid maksimal 2 kolom ke kanan, memanjang ke bawah |
| 6 | Card Contact Perusahaan | Sticky saat scroll, hanya di area body, berhenti sebelum footer |
| 7 | Card Blog | Grid maksimal 3 kolom ke kanan, memanjang ke bawah |
| 8 | Sort Perusahaan | Latest / Oldest |

## 3.3 Aturan Admin Internal

| # | Aturan | Ketentuan |
| --- | --- | --- |
| 1 | Post job oleh admin | Form dan tampilan mengikuti Post Job Employer; perusahaan tidak perlu memposting sendiri |
| 2 | Hasil post job | Tampil di Halaman Loker publik seperti loker Employer |
| 3 | Pemilihan perusahaan | Admin menentukan perusahaan pemilik loker (terdaftar atau baru) |
| 4 | Artikel | Hasil artikel tampil di Menu Blog; topik: info lowongan, info perusahaan, cara cari kerja, dll. |
| 5 | Monitoring | Admin dapat memantau perusahaan yang terdaftar di Start Work |
| 6 | Tampilan | Web CMS, mengacu pada simbarefinery.com |
| 7 | Package Employer | Basic: Rp 99.000, kuota 10 loker / 15 hari; Standard: Rp 149.000, kuota 20 loker / 30 hari; Premium: Rp 239.000, kuota 30 loker / 45 hari. Posting hanya dapat dilakukan saat package aktif dan sisa kuota tersedia. |
| 8 | Pembayaran package | Employer memilih package dan menyelesaikan pembayaran sesuai harga package (Basic Rp 99.000, Standard Rp 149.000, Premium Rp 239.000). Package aktif setelah pembayaran terkonfirmasi; package yang kedaluwarsa tidak dapat digunakan untuk posting baru. Gateway/metode pembayaran masih perlu ditentukan. |
| 9 | Kelola Loker Admin | Admin memantau katalog package dan status perusahaan/langganan; dapat memberi trial 7 hari kepada perusahaan yang belum berlangganan menggunakan kuota package yang dipilih; dapat melihat lowongan, pelamar, dan data diri/CV sesuai hak akses. |

## 3.4 Aturan Akun Jobseeker

| # | Aturan | Ketentuan |
| --- | --- | --- |
| 1 | Registrasi dan login | Jobseeker dapat membuat akun dan login |
| 2 | Field registrasi | Email, kata sandi, dan nomor telepon; kata sandi minimal 8 karakter mengikuti aturan Employer |
| 3 | Metode login | Email dan kata sandi; login Google dan lupa kata sandi mengikuti Employer |
| 4 | Data diri | Diisi Jobseeker pada profilnya; daftar field dan status wajib/opsional pada Bagian 3.5 |
| 5 | CV | Wajib, format PNG/JPEG/PDF, maksimal 5 MB |
| 6 | Foto profil | Wajib, format JPG/JPEG/PNG, maksimal 2 MB |
| 7 | Akses CV | Employer dan Admin Internal dapat melihat CV Jobseeker |
| 8 | Penyimpanan file | Storage privat karena foto dan CV memuat data pribadi |
| 9 | Alur dari landing | Search mengarah ke halaman Loker; klik card lowongan langsung ke Job Detail |
| 10 | Browsing publik | Pencarian loker, perusahaan, blog, dan FAQs tetap dapat diakses tanpa login |

## 3.5 Field Data Diri Jobseeker

Daftar field berasal dari PIC. Field identitas, foto profil, pendidikan terakhir, skills, dan CV wajib diisi, sedangkan riwayat pendidikan, pengalaman kerja/magang, pengalaman organisasi dan kepanitiaan, serta informasi tambahan (sertifikat dan link portofolio) bersifat opsional.

| # | Field | Input dan Validasi | Status | Alasan |
| --- | --- | --- | --- | --- |
| 1 | Foto profil | Upload; JPG/JPEG/PNG; maks. 2 MB | Wajib | Identitas visual Jobseeker yang dilihat Employer |
| 2 | Nama lengkap | Teks | Wajib | Identitas dasar yang dilihat Employer dan Admin |
| 3 | Lokasi domisili saat ini | Dropdown dengan fitur search | Wajib | Dasar kecocokan dengan lokasi loker |
| 4 | Tanggal lahir | Date picker; tidak boleh tanggal masa depan | Wajib | Data screening dasar |
| 5 | Jenis kelamin | Dropdown; opsi perlu ditentukan | Wajib | Data identitas dasar, melengkapi field di atas |
| 6 | Pendidikan terakhir | Dropdown SD sampai S3 | Wajib | Syarat minimal yang paling sering dipakai Employer |
| 7 | Pengalaman kerja/magang | Dapat lebih dari satu entri. Sub-field: Pekerjaan, Nama pekerjaan, Perusahaan, Tanggal mulai, Tanggal selesai, Key Responsibilities. Jika entri ditambah: Nama pekerjaan, Perusahaan, Tanggal mulai wajib; Tanggal selesai wajib kecuali masih berlangsung | Opsional | Pencari kerja baru belum tentu punya pengalaman; detail ada di CV |
| 8 | Pengalaman organisasi dan kepanitiaan | Dapat lebih dari satu entri: nama organisasi, jabatan, tanggal mulai, tanggal selesai, dan deskripsi | Opsional | Pelengkap, tidak dibutuhkan semua pelamar |
| 9 | Pendidikan (riwayat) | Dapat lebih dari satu entri. Sub-field: Tingkat Pendidikan, Nama sekolah/perguruan tinggi, Jurusan, Tanggal masuk, Tanggal keluar. Jurusan wajib mulai SMA/SMK sederajat; tanggal keluar kosong bila masih studi | Opsional | Melengkapi pendidikan terakhir dengan detail institusi |
| 10 | Skills | Daftar skill, minimal satu dan dapat lebih dari satu | Wajib | Membantu Employer menilai kecocokan pelamar |
| 11 | Informasi tambahan: sertifikat | Dapat lebih dari satu; unggah file PDF/PNG/JPEG | Opsional | Pelengkap |
| 12 | Informasi tambahan: link portofolio | URL valid | Opsional | Tidak semua profesi memiliki portofolio |
| 13 | CV | Upload; PNG/JPEG/PDF; maks. 5 MB | Wajib | Ditetapkan PIC |

## 3.6 Aturan Lamaran

| # | Aturan | Ketentuan |
| --- | --- | --- |
| 1 | Cara melamar | Jobseeker menekan tombol Apply pada Job Detail. Lamaran dikirim di dalam platform dan menggantikan link apply eksternal |
| 2 | Syarat melamar | Jobseeker sudah login dan data wajib serta CV sudah terisi |
| 3 | Belum login | Jobseeker diarahkan ke login atau registrasi, lalu kembali ke Job Detail |
| 4 | Satu lamaran per loker | Jobseeker tidak dapat melamar dua kali ke loker yang sama |
| 5 | Data lamaran | Berisi loker yang dilamar, profil Jobseeker (data diri dan CV), dan waktu apply |
| 6 | Tampil di Jobseeker | Tersimpan pada profil di tab Lamaran Saya: posisi, perusahaan, tanggal apply, dan status |
| 7 | Tampil di Employer | Muncul pada menu Pelamar milik Employer. Employer hanya melihat pelamar pada loker miliknya |
| 8 | Status lamaran | Status awal "Terkirim". Perubahan status oleh Employer belum ditentukan |
| 9 | Akses data pelamar | Employer melihat data diri dan CV pelamar; Jobseeker lain tidak dapat melihatnya |

## 3.7 Aturan Package Loker Employer

Package dibeli per perusahaan dan hanya dapat digunakan untuk membuat lowongan selama masa aktifnya. Setiap posting yang berhasil memakai satu kuota. Pembayaran yang belum terkonfirmasi tidak mengaktifkan package. Admin dapat memberi trial 7 hari kepada perusahaan yang belum berlangganan; trial menggunakan kuota package yang dipilih.

Daftar package:

| Package | Kuota posting | Masa aktif | Harga |
| --- | --- | --- | --- |
| Basic | 10 loker | 15 hari | Rp 99.000 |
| Standard | 20 loker | 30 hari | Rp 149.000 |
| Premium | 30 loker | 45 hari | Rp 239.000 |

Ketentuan umum package:

| # | Aturan | Ketentuan |
| --- | --- | --- |
| 1 | Kepemilikan | Package dibeli per perusahaan dan hanya dapat digunakan untuk membuat lowongan selama masa aktifnya |
| 2 | Pemakaian kuota | Setiap posting yang berhasil memakai 1 kuota |
| 3 | Aktivasi | Pembayaran yang belum terkonfirmasi tidak mengaktifkan package |
| 4 | Kedaluwarsa atau kuota habis | Employer tidak dapat memposting loker baru sampai membeli atau memperpanjang package |
| 5 | Trial | Admin dapat memberi trial 7 hari kepada perusahaan yang belum berlangganan; trial memakai kuota package pilihan Admin. |
| 6 | Harga dan pembayaran | Harga mengikuti tabel package di atas (Basic Rp 99.000, Standard Rp 149.000, Premium Rp 239.000); gateway/metode pembayaran masih perlu ditentukan (Bagian 6) |

# 4. Fitur Lengkap Per Aktor

## 4.1 Jobseeker

Landing Page

Section 1: search jabatan/info lowongan, filter lokasi, tombol "Cari Lowongan", chip pencarian populer berbentuk rounded box (contoh: Teknologi, Pemasaran, Desain). Klik "Cari Lowongan" mengarah ke halaman Loker

Section 2 (Lowongan Terbaru): preview beberapa card lowongan dari perusahaan yang membuka lowongan; klik card langsung membuka Job Detail perusahaan tersebut tanpa melalui halaman Loker, tombol "Lihat Semua Lowongan Kerja" ke halaman Loker

Section 3: card daftar sebagai employer, dan card "Rekrut Talenta Terbaik" dengan tombol "Pasang Lowongan Kerja"

Menu Loker

Search loker dan filter lokasi (pojok kanan atas)

Filter kiri: Category, Job Type, Work Type, Date Posted

Card info lowongan di tengah, dengan pengaturan jumlah kolom dan sort Latest/Oldest/A-Z/Z-A

Job Detail: card kiri (dominan) berisi header (logo perusahaan, job title, nama perusahaan, lokasi, sistem kerja), Deskripsi Pekerjaan, Tanggung Jawab, Keahlian

Job Detail: card kanan berisi Tipe Kerja, Sistem Kerja, Lokasi, Gaji, Kategori, Waktu diposting, dengan tombol Apply di atasnya (lamaran dikirim di dalam platform)

Menu Perusahaan

Card semua perusahaan terdaftar, search nama, filter lokasi, sort Latest/Oldest

Detail perusahaan: 3 card utama di atas, 2 card di bawah (kiri dominan Open Positions, kanan tombol Contact yang sticky)

Klik card Open Position langsung membuka job detail di halaman Loker

Halaman Informasi

About Us: perkenalan platform, apa yang dilakukan, nilai-nilai, cara kerja, tombol CTA, pertanyaan umum

FAQs: kategori Cara Melamar Pekerjaan, CV & Dokumen, Jenis Pekerjaan, Keamanan, Status Lamaran, Akun & Profil, Tentang Startwork, Untuk Perusahaan, Bantuan & Kontak

Blog: card artikel (foto, tanggal, judul, "Baca Selengkapnya →"), klik menuju halaman detail artikel

Akun Jobseeker

Registrasi: Jobseeker membuat akun baru melalui modal login → "Daftar Akun" (email, kata sandi, nomor telepon)

Login: Jobseeker masuk ke akunnya; lupa kata sandi

Isi data diri: foto profil, nama lengkap, lokasi domisili (dropdown + search), tanggal lahir (date picker), jenis kelamin, pendidikan terakhir (SD sampai S3), pengalaman kerja/magang, pengalaman organisasi dan kepanitiaan, riwayat pendidikan, skills, informasi tambahan (sertifikat, link portofolio); status wajib/opsional pada Bagian 3.5

Upload CV: wajib; PNG/JPEG/PDF, maksimal 5 MB

Profil dan Lamaran Jobseeker

Profil memiliki dua tab: Data Diri & CV dan Lamaran Saya

Lamaran Saya menampung seluruh lowongan yang sudah di-apply: posisi, perusahaan, tanggal apply, dan status

Apply: tombol Apply pada Job Detail; Jobseeker harus login dan profil (data wajib dan CV) sudah lengkap

## 4.2 Employer

Akun & Onboarding

Login: card dua sisi kiri-kanan (kanan dominan), input Email & Kata sandi, "Lupa kata sandi?", tombol Masuk, opsi lanjutkan dengan Google, link "Belum punya akun? Daftar di sini"

Registrasi: Data Diri (Nama lengkap, Nomor Telepon, Email perusahaan), Keamanan akun (Kata sandi min. 8 karakter, Konfirmasi), tombol "Buat akun"

Stepper Company Setup (step 2): Nama Perusahaan, Lokasi Perusahaan, Logo (opsional), Website (opsional), URL Kontak (opsional); tombol "Simpan & Lanjutkan" dan "Kembali"

Dashboard

Card KPI: Loker Tayang (tampil di website), Sisa Kuota (slot posting tersisa), Total Loker (semua lowongan)

Daftar postingan loker yang sudah dibuat

Ringkasan package aktif: nama package, masa berlaku, kuota total, kuota terpakai, dan sisa kuota.

Jika kosong: tombol "+ Tambah loker pertama" menuju Post Job

Jika belum memiliki package aktif atau kuota habis: tombol tambah loker mengarahkan Employer untuk memilih dan membayar package; Employer tidak dapat mempublikasikan loker sebelum pembayaran berhasil.

Halaman pilih package menampilkan Basic (Rp 99.000; 10 loker; 15 hari), Standard (Rp 149.000; 20 loker; 30 hari), dan Premium (Rp 239.000; 30 loker; 45 hari)

Post Job

Sebelum form posting dapat dipublikasikan, sistem memeriksa package aktif, masa berlaku, dan sisa kuota. Satu loker yang berhasil dibuat mengurangi kuota satu slot. Package yang kedaluwarsa atau kuotanya habis tidak dapat dipakai untuk posting baru.

Informasi dasar: Judul Posisi, Kategori, Lokasi, Tipe Pekerjaan, Sistem Kerja

Rentang gaji (opsional): Gaji minimum, Gaji maksimum

Deskripsi & persyaratan: Deskripsi Pekerjaan, Tanggung Jawab, Persyaratan, Keahlian, Benefit

Tombol "Posting Loker" dan "Batal"

Settings (3 tab, tab aktif ditandai)

Profile Pribadi: Nama lengkap, Nomor telepon, Posisi/jabatan, Lokasi, Email

Perusahaan: Upload logo, Nama Perusahaan, Lokasi perusahaan, Website (opsional), Deskripsi Perusahaan (opsional)

Keamanan: Kata sandi saat ini, Kata sandi baru, Konfirmasi kata sandi baru

Pelamar

Menu Pelamar menampilkan lamaran yang masuk dari Jobseeker ke loker milik Employer: nama pelamar, posisi yang dilamar, dan tanggal apply

Employer dapat memilih satu pelamar untuk melihat data diri dan CV-nya

Pada Dashboard, jumlah pelamar ditampilkan pada setiap loker.

## 4.3 Admin Internal (Role Baru)

Fitur Admin Internal dijabarkan dalam PRD ini sebagai kebutuhan sistem. Hak akses Admin Internal dibatasi dan seluruh tindakan perubahan data dicatat untuk audit.

Akses & Tampilan

Tampilan berupa web CMS untuk Start Work dengan menu Kelola Loker sebagai pusat pengelolaan package, langganan, perusahaan, lowongan, dan pelamar.

Login khusus admin dengan pembatasan akses per role

Task 1: Kelola Loker (Package, Langganan, Perusahaan, Lowongan, Pelamar)

Memposting lowongan dari perusahaan lain yang ingin mengiklankan lamarannya di Start Work tanpa perusahaan tersebut memposting sendiri

Tampilan form seperti Post Job di Employer (informasi dasar, rentang gaji opsional, deskripsi & persyaratan)

Memilih atau mengisi perusahaan pemilik loker

Penanda "diposting oleh admin" untuk keperluan audit

Task 2: Membuat Artikel / News

Membuat artikel/news terkait info lowongan pekerjaan, info perusahaan, cara cari kerja, dan topik sejenis

Artikel tampil di Menu Blog (foto, tanggal, judul, halaman detail)

Edit, hapus, dan status draft/publish

Subtask: Monitoring Package, Langganan, Perusahaan, Lowongan, dan Pelamar

Melihat daftar perusahaan yang terdaftar beserta status langganan package; detail perusahaan memuat package/trial, lowongan, pelamar, dan data diri/CV Jobseeker terkait.

Memfilter perusahaan terdaftar berdasarkan status langganan (aktif, trial, kedaluwarsa, belum berlangganan) dan melihat detail perusahaan, histori package/langganan, serta lowongan yang diposting.

Untuk perusahaan terdaftar yang belum berlangganan package, Admin dapat memberikan trial 7 hari dan melihat tanggal mulai/berakhir trial serta statusnya. Trial terikat ke package yang dipilih, dan kuotanya mengikuti package tersebut.

Melihat daftar pelamar/jobseeker berdasarkan perusahaan dan lowongan yang dilamar, lalu membuka data diri dan CV Jobseeker sesuai hak akses Admin Internal. Relasi perusahaan ditentukan dari lowongan pada lamaran.

Memantau katalog package Basic, Standard, dan Premium beserta kuota serta masa aktifnya; Admin dapat mengelola status ketersediaan package.

Data perusahaan minimal: nama, lokasi, website, logo, jumlah lowongan, status package/langganan, kuota, dan masa berlaku.

Subtask: Melihat Data Diri dan CV Jobseeker

Admin Internal dapat menelusuri perusahaan → lowongan → pelamar/jobseeker dan melihat data diri serta CV pelamar melalui akses terlindungi yang mencatat audit akses.

# 5. Struktur Halaman & Navigasi

| # | Halaman | Role | Route | Keterangan |
| --- | --- | --- | --- | --- |
| 1 | Landing Page | Jobseeker | / | 3 section |
| 2 | Loker | Jobseeker | /loker | Daftar, filter, sort |
| 3 | Job Detail | Jobseeker | /loker/:id | Dua card, tombol Apply |
| 4 | Perusahaan | Jobseeker | /perusahaan | Daftar, search, filter, sort |
| 5 | Detail Perusahaan | Jobseeker | /perusahaan/:id | Open Positions, Contact |
| 6 | About Us | Jobseeker | /about-us | Statis |
| 7 | FAQs | Jobseeker | /faqs | 9 kategori |
| 8 | Blog | Jobseeker | /blog | Grid max. 3 kolom |
| 9 | Detail Artikel | Jobseeker | /blog/:id | Isi artikel |
| 10 | Registrasi Jobseeker (modal) | Jobseeker | /register | Modal login → "Daftar Akun" |
| 11 | Login Jobseeker (modal) | Jobseeker | /login | Modal dari tombol Login |
| 12 | Profil: Data Diri & CV | Jobseeker | /profil | Tab Data Diri & CV |
| 13 | Profil: Lamaran Saya | Jobseeker | /profil/lamaran | Daftar lamaran yang sudah di-apply |
| 14 | Login Employer | Employer | /employer/login | Email + Google |
| 15 | Registrasi Employer | Employer | /employer/register | Data diri & keamanan |
| 16 | Company Setup | Employer | /employer/setup | Stepper step 2 |
| 17 | Dashboard | Employer | /employer/dashboard | KPI + daftar loker |
| 18 | Pilih Package Employer | Employer | /employer/packages | Daftar package, harga, kuota/durasi, dan pilih package |
| 19 | Pembayaran Package | Employer | /employer/packages/checkout | Checkout dan status pembayaran; package aktif setelah konfirmasi |
| 20 | Post Job | Employer | /employer/post-job | Form posting |
| 21 | Daftar Pelamar | Employer | /employer/pelamar | Lamaran masuk ke loker milik Employer |
| 22 | Detail Pelamar & CV | Employer | /employer/pelamar/:id | Data diri dan CV pelamar |
| 23 | Settings | Employer | /employer/settings | 3 tab |
| 24 | Kelola Loker (Admin) | Admin Internal | /admin/jobs | Submenu: Package, Langganan, Perusahaan, Lowongan, Pelamar |
| 25 | Post Job (Admin) | Admin Internal | /admin/jobs/new | Kelola Loker → Lowongan; admin memposting atas nama perusahaan |
| 26 | Detail Lowongan (Admin) | Admin Internal | /admin/jobs/:id | Detail lowongan dan daftar pelamar |
| 27 | Monitoring Perusahaan (Admin) | Admin Internal | /admin/companies | Daftar perusahaan, status package/langganan/trial, lowongan, dan pelamar |
| 28 | Detail Perusahaan (Admin) | Admin Internal | /admin/companies/:id | Profil, status package/trial, histori langganan, lowongan dan pelamar perusahaan |
| 29 | Detail Jobseeker/Pelamar (Admin) | Admin Internal | /admin/jobseekers/:id | Data diri dan CV dengan pemeriksaan otorisasi |
| 30 | Artikel (Admin) | Admin Internal | /admin/articles | Task artikel pada CMS Admin |

# 6. Use Case Diagram

Berikut diagram dan daftar use case yang dimiliki setiap aktor dalam sistem Start Work. Garis putus-putus menunjukkan relasi «include»/«extend», sedangkan use case berwarna hijau menandai fitur baru.

## 6.1 Use Case — Jobseeker

![Diagram/gambar dari dokumen sumber](assets/image_06.png)

Gambar 7.1a Use Case Diagram — Jobseeker: Pencarian & Informasi

![Diagram/gambar dari dokumen sumber](assets/image_30.png)

Gambar 7.1b Use Case Diagram — Jobseeker: Akun, Profil & Lamaran

Deskripsi use case:

| Use Case ID | Nama Use Case | Deskripsi Singkat |
| --- | --- | --- |
| UC-J01 | Cari Lowongan | Jobseeker mencari berdasarkan jabatan dan lokasi dari landing page; tombol "Cari Lowongan" mengarahkan ke halaman Loker |
| UC-J02 | Gunakan Pencarian Populer | Jobseeker memilih chip kategori populer (Teknologi, Pemasaran, Desain) |
| UC-J03 | Lihat Lowongan Terbaru | Jobseeker melihat preview lowongan dari perusahaan yang membuka lowongan di landing page; klik card langsung membuka Job Detail |
| UC-J04 | Filter & Sort Loker | Jobseeker memfilter Category/Job Type/Work Type/Date Posted dan mengurutkan hasil |
| UC-J05 | Lihat Job Detail | Jobseeker membuka detail lowongan dan informasinya |
| UC-J06 | Apply Lowongan | Jobseeker menekan tombol Apply pada Job Detail; lamaran tersimpan di Lamaran Saya dan tampil di daftar pelamar Employer. Mensyaratkan login dan CV terunggah |
| UC-J07 | Lihat Daftar Perusahaan | Jobseeker mencari, memfilter, dan mengurutkan perusahaan |
| UC-J08 | Lihat Detail Perusahaan | Jobseeker melihat Open Positions perusahaan |
| UC-J09 | Hubungi Perusahaan | Jobseeker menekan tombol Contact |
| UC-J10 | Baca About Us & FAQs | Jobseeker membaca informasi platform dan pertanyaan umum |
| UC-J11 | Baca Blog | Jobseeker membaca daftar dan detail artikel |
| UC-J12 | Registrasi Akun Jobseeker | Jobseeker membuat akun baru melalui modal login → Daftar Akun (email, kata sandi, nomor telepon) |
| UC-J13 | Login Jobseeker | Jobseeker masuk ke akunnya; metode login perlu dikonfirmasi |
| UC-J14 | Lupa Kata Sandi | Jobseeker memulihkan akses akun |
| UC-J15 | Isi Data Diri | Jobseeker mengisi data diri (field dan status wajib/opsional pada Bagian 3.5) |
| UC-J16 | Upload CV | Jobseeker mengunggah CV (wajib; PNG/JPEG/PDF, maks. 5 MB) sebagai bagian dari data diri; di-include oleh UC-J15 |
| UC-J17 | Lihat Lamaran Saya | Jobseeker melihat daftar lowongan yang sudah di-apply pada tab Lamaran Saya di profil (posisi, perusahaan, tanggal apply, status) |

## 6.2 Use Case — Employer

![Diagram/gambar dari dokumen sumber](assets/image_24.png)

Gambar 7.2 Use Case Diagram — Employer

Deskripsi use case:

| Use Case ID | Nama Use Case | Deskripsi Singkat |
| --- | --- | --- |
| UC-E01 | Registrasi Akun | Employer mendaftar dengan data diri dan kata sandi |
| UC-E02 | Login | Employer masuk dengan email/kata sandi atau Google |
| UC-E03 | Lupa Kata Sandi | Employer memulihkan akses akun |
| UC-E04 | Company Setup | Employer mengisi profil perusahaan pada stepper |
| UC-E05 | Lihat Dashboard | Employer melihat KPI dan daftar loker |
| UC-E06 | Post Job | Employer membuat dan memposting lowongan |
| UC-E07 | Kelola Profile Pribadi | Employer mengubah data pribadi |
| UC-E08 | Kelola Profil Perusahaan | Employer mengubah data perusahaan dan logo |
| UC-E09 | Ubah Kata Sandi | Employer mengganti kata sandi pada tab Keamanan |
| UC-E10 | Lihat Daftar Pelamar | Employer melihat Jobseeker yang melamar ke loker miliknya (nama, posisi dilamar, tanggal apply) |
| UC-E11 | Lihat CV Pelamar | Employer membuka data diri dan CV pelamar dari daftar pelamar |
| UC-E12 | Pilih dan Beli Package | Employer memilih Basic (Rp 99.000), Standard (Rp 149.000), atau Premium (Rp 239.000), menyelesaikan pembayaran, dan mendapat kuota saat transaksi terkonfirmasi. |
| UC-E13 | Lihat Status Package | Employer melihat package aktif, kuota terpakai/sisa kuota, masa berlaku, dan status pembayaran. |

## 6.3 Use Case — Admin Internal

![Diagram/gambar dari dokumen sumber](assets/image_21.png)

Gambar 7.3 Use Case Diagram — Admin Internal

Deskripsi use case:

| Use Case ID | Nama Use Case | Deskripsi Singkat |
| --- | --- | --- |
| UC-A01 | Login Admin | Admin masuk ke panel CMS |
| UC-A02 | Post Job atas Nama Perusahaan | Admin memposting lowongan dari perusahaan lain tanpa perusahaan memposting sendiri |
| UC-A03 | Buat Artikel / News | Admin membuat artikel yang tampil di Blog |
| UC-A04 | Kelola Artikel | Admin mengedit, menghapus, atau mengatur publikasi artikel |
| UC-A05 | Monitoring Perusahaan | Admin memantau perusahaan yang terdaftar |
| UC-A06 | Edit / Hapus Loker | Admin mengedit/menghapus loker |
| UC-A07 | Kelola Package | Admin melihat dan mengelola ketersediaan katalog package dan atribut kuota/durasi. |
| UC-A08 | Monitoring Langganan Perusahaan | Admin memfilter status package; melihat perusahaan belum berlangganan dan memberikan trial 7 hari. |
| UC-A09 | Lihat Lowongan per Perusahaan | Admin membuka detail perusahaan dan melihat seluruh lowongan yang diposting perusahaan tersebut. |
| UC-A10 | Lihat Pelamar per Perusahaan | Admin menelusuri perusahaan → lowongan → pelamar/jobseeker yang mendaftar. |
| UC-A11 | Lihat Data Diri dan CV Jobseeker | Admin melihat data diri dan CV pelamar dari lowongan/perusahaan yang dipilih, melalui akses terlindungi sesuai hak akses; akses tercatat pada audit log |
| UC-A12 | Berikan Trial 7 Hari | Admin memberi trial 7 hari kepada perusahaan terdaftar yang belum memiliki package aktif dan memantau status trial. |

# 7. Activity Diagram

Activity diagram menggambarkan alur aktivitas dalam setiap proses utama sistem, dilengkapi swimlane per pelaku dan tabel langkah aktivitas. Kebutuhan Admin Internal dirinci pada alur 8.11–8.14.

## 7.1 Alur Jobseeker Registrasi Akun (melalui Modal Login → Daftar Akun)

![Diagram/gambar dari dokumen sumber](assets/image_14.png)

Gambar 8.1 Activity Diagram — Alur Jobseeker Registrasi Akun

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Jobseeker | Mengklik button Login  |
| 2 | Jobseeker | Pada modal login Jobseeker klik “Daftar Akun” |
| 3 | Jobseeker | Mengisi data akun (email, password, nomor telepon) lalu menekan "Buat akun" |
| 4 | Sistem | Memvalidasi data (email belum terdaftar, kata sandi memenuhi aturan) |
| 5 | Sistem | [Jika tidak valid] Menampilkan pesan error, Jobseeker memperbaiki data |
| 6 | Sistem | [Jika valid] Membuat akun dan sesi login |
| 7 | Sistem | Mengarahkan Jobseeker ke halaman utama loker |

## 7.2 Alur Jobseeker Login

![Diagram/gambar dari dokumen sumber](assets/image_07.png)

Gambar 8.2 Activity Diagram — Alur Jobseeker Login

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Jobseeker | Membuka halaman login Jobseeker |
| 2 | Jobseeker | [Jika lupa kata sandi] Menuju alur pemulihan kata sandi |
| 3 | Jobseeker | Mengisi email dan kata sandi lalu menekan "Masuk" |
| 4 | Sistem | Memverifikasi kredensial |
| 5 | Sistem | [Jika salah] Menampilkan pesan error, Jobseeker mengisi ulang |
| 6 | Sistem | [Jika benar] Membuat sesi login dan mengarahkan ke halaman utama; bila login dipicu tombol Apply, kembali ke Job Detail |

## 7.3 Alur Jobseeker Mengisi Data Diri dan Upload CV

![Diagram/gambar dari dokumen sumber](assets/image_01.png)

Gambar 8.3 Activity Diagram — Alur Jobseeker Mengisi Data Diri dan Upload CV

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Jobseeker | Membuka halaman Isi Data Diri (setelah registrasi atau dari profil) |
| 2 | Jobseeker | Mengisi data wajib: foto profil, nama lengkap, lokasi domisili, tanggal lahir, jenis kelamin, pendidikan terakhir, skills |
| 3 | Jobseeker | Mengisi data opsional: riwayat pendidikan, pengalaman kerja/magang, organisasi, sertifikat, link portofolio |
| 4 | Jobseeker | Memilih file CV (wajib) lalu menekan "Simpan" |
| 5 | Sistem | Memvalidasi data wajib, foto (JPG/JPEG/PNG, maks. 2 MB), dan CV (PNG/JPEG/PDF, maks. 5 MB) |
| 6 | Sistem | [Jika tidak valid] Menampilkan pesan error, Jobseeker memperbaiki data |
| 7 | Sistem | [Jika valid] Menyimpan data diri, foto, dan file CV ke profil |
| 8 | Sistem | Menampilkan profil dengan CV yang sudah terunggah |

## 7.4 Alur Jobseeker Mencari Lowongan hingga Job Detail

![Diagram/gambar dari dokumen sumber](assets/image_25.png)

Gambar 8.4 Activity Diagram — Alur Jobseeker Mencari Lowongan hingga Job Detail

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Jobseeker | Mengisi jabatan dan lokasi di landing page lalu menekan "Cari Lowongan" |
| 2 | Sistem | Menampilkan halaman Loker dengan hasil pencarian |
| 3 | Jobseeker | Menerapkan filter (Category, Job Type, Work Type, Date Posted) dan sort |
| 4 | Sistem | Memperbarui daftar card lowongan |
| 5 | Jobseeker | [Jika tidak ada lowongan sesuai] Mengubah kata kunci atau filter |
| 6 | Jobseeker | [Jika ada] Mengklik card lowongan |
| 7 | Sistem | Menampilkan Job Detail (card kiri dan kanan); lanjut ke alur Apply (8.6) |

## 7.5 Alur Jobseeker Membuka Job Detail dari Card di Landing Page

![Diagram/gambar dari dokumen sumber](assets/image_17.png)

Gambar 8.5 Activity Diagram — Alur Jobseeker Membuka Job Detail dari Card di Landing Page

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Jobseeker | Membuka landing page |
| 2 | Sistem | Menampilkan preview lowongan dari perusahaan yang membuka lowongan |
| 3 | Jobseeker | Mengklik card lowongan pada preview |
| 4 | Sistem | Menampilkan Job Detail perusahaan tersebut langsung, tanpa melalui halaman Loker; lanjut ke alur Apply (8.6) |

## 7.6 Alur Jobseeker Melamar Lowongan (Apply)

![Diagram/gambar dari dokumen sumber](assets/image_11.png)

Gambar 8.6 Activity Diagram — Alur Jobseeker Melamar Lowongan (Apply)

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Jobseeker | Menekan tombol "Apply" pada Job Detail |
| 2 | Sistem | Memeriksa status login. [Jika belum login] Mengarahkan ke Login, lalu kembali ke Job Detail |
| 3 | Sistem | Memeriksa kelengkapan profil dan CV. [Jika belum lengkap] Mengarahkan ke Isi Data Diri |
| 4 | Sistem | Memeriksa apakah Jobseeker sudah pernah melamar ke loker ini. [Jika sudah] Menampilkan pesan |
| 5 | Sistem | [Jika valid] Menyimpan lamaran (loker, profil, CV, waktu apply) |
| 6 | Sistem | Menampilkan konfirmasi "Lamaran terkirim" |
| 7 | Sistem | Lamaran tampil di Lamaran Saya dan di daftar pelamar Employer |

## 7.7 Alur Jobseeker Melihat Lamaran Saya

![Diagram/gambar dari dokumen sumber](assets/image_08.png)

Gambar 8.7 Activity Diagram — Alur Jobseeker Melihat Lamaran Saya

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Jobseeker | Membuka halaman Profil dan memilih tab "Lamaran Saya" |
| 2 | Sistem | Mengambil daftar lamaran milik Jobseeker |
| 3 | Sistem | [Jika belum ada lamaran] Menampilkan informasi belum ada lamaran |
| 4 | Sistem | [Jika ada] Menampilkan daftar lamaran (posisi, perusahaan, tanggal apply, status) |

## 7.8 Alur Registrasi & Company Setup Employer

![Diagram/gambar dari dokumen sumber](assets/image_02.png)

Gambar 8.8 Activity Diagram — Alur Registrasi & Company Setup Employer

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Employer | Membuka halaman registrasi dari link "Daftar di sini" |
| 2 | Employer | Mengisi Nama lengkap, Nomor Telepon, Email perusahaan, Kata sandi, Konfirmasi kata sandi |
| 3 | Sistem | Memvalidasi data (kata sandi min. 8 karakter dan sama dengan konfirmasi) |
| 4 | Sistem | [Jika tidak valid] Menampilkan pesan error |
| 5 | Sistem | [Jika valid] Membuat akun dan mengarahkan ke Stepper Company Setup |
| 6 | Employer | Mengisi profil perusahaan lalu menekan "Simpan & Lanjutkan" |
| 7 | Sistem | Menyimpan data dan mengarahkan ke Dashboard Employer |

## 7.9 Alur Employer Memposting Loker

![Diagram/gambar dari dokumen sumber](assets/image_22.png)

Gambar 8.9 Activity Diagram — Alur Employer Memposting Loker

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Employer | Membuka Dashboard dan memilih package bila belum memiliki package aktif atau kuota. |
| 2 | Employer | Memilih Basic (Rp 99.000; 10 loker/15 hari), Standard (Rp 149.000; 20 loker/30 hari), atau Premium (Rp 239.000; 30 loker/45 hari). |
| 3 | Sistem | Menampilkan rincian package (harga, kuota, masa aktif) dan mengarahkan ke pembayaran. |
| 4 | Sistem | Mengaktifkan package dan kuota setelah pembayaran terkonfirmasi; jika gagal, package tidak aktif. |
| 5 | Employer | Mengisi form Post Job dan menekan "Posting Loker". |
| 6 | Sistem | Memvalidasi package belum kedaluwarsa dan sisa kuota lebih dari nol. Jika tidak, meminta Employer membeli package atau memperpanjang langganan. |
| 7 | Sistem | Menyimpan loker, mengurangi sisa kuota satu, dan menetapkan tanggal akhir tayang sesuai aturan masa loker. |
| 8 | Sistem | Memperbarui KPI Dashboard dan menampilkan loker pada website publik. |

## 7.10 Alur Employer Melihat Pelamar dan CV

![Diagram/gambar dari dokumen sumber](assets/image_12.png)

Gambar 8.10 Activity Diagram — Alur Employer Melihat Pelamar dan CV

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Employer | Membuka menu Pelamar (opsional memilih loker) |
| 2 | Sistem | Mengambil lamaran yang masuk ke loker milik Employer |
| 3 | Sistem | [Jika belum ada pelamar] Menampilkan informasi belum ada pelamar |
| 4 | Sistem | [Jika ada] Menampilkan daftar pelamar (nama, posisi dilamar, tanggal apply) |
| 5 | Employer | Memilih pelamar |
| 6 | Sistem | Menampilkan data diri dan CV pelamar |
| 7 | Employer | Melihat CV pelamar |

## 7.11 Alur Admin Memposting Loker atas Nama Perusahaan Lain

![Diagram/gambar dari dokumen sumber](assets/image_04.png)

Gambar 8.11 Activity Diagram — Alur Admin Memposting Loker atas Nama Perusahaan Lain

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Admin | Login ke panel CMS dan membuka menu Post Job |
| 2 | Admin | Menentukan perusahaan pemilik loker |
| 3 | Admin | Mengisi form posting (informasi dasar, gaji opsional, deskripsi & persyaratan) |
| 4 | Admin | Menekan tombol Posting |
| 5 | Sistem | Memvalidasi dan menyimpan loker dengan penanda diposting oleh admin |
| 6 | Sistem | Menampilkan loker di halaman Loker publik |

## 7.12 Alur Admin Membuat Artikel

![Diagram/gambar dari dokumen sumber](assets/image_26.png)

Gambar 8.12 Activity Diagram — Alur Admin Membuat Artikel

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Admin | Membuka menu Artikel di panel CMS |
| 2 | Admin | Mengisi judul, foto, dan isi artikel |
| 3 | Admin | Menyimpan/mempublikasikan artikel |
| 4 | Sistem | Menyimpan artikel |
| 5 | Sistem | Menampilkan artikel sebagai card di Menu Blog beserta halaman detailnya |

## 7.13 Alur Admin Kelola Package, Langganan, dan Trial Perusahaan

![Diagram/gambar dari dokumen sumber](assets/image_18.png)

Gambar 8.13 Activity Diagram — Alur Admin Kelola Package, Langganan, dan Trial

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Admin | Membuka Kelola Loker → Package untuk melihat katalog, kuota, dan durasi package. |
| 2 | Admin | Membuka Perusahaan dan memfilter yang belum berlangganan atau berstatus trial/kedaluwarsa. |
| 3 | Sistem | Menampilkan profil perusahaan, status/langganan, histori package, dan lowongan perusahaan. |
| 4 | Admin | Memberikan trial 7 hari kepada perusahaan yang belum memiliki package aktif; memilih package untuk menentukan kuota trial. |
| 5 | Sistem | Mencatat pemberi trial, waktu mulai/akhir, package, kuota, dan status trial. |

## 7.14 Alur Admin Melihat Pelamar dan Data Diri Jobseeker

![Diagram/gambar dari dokumen sumber](assets/image_15.png)

Gambar 8.14 Activity Diagram — Alur Admin Melihat Pelamar dan Data Diri Jobseeker

Tabel langkah aktivitas:

| Step | Pelaku | Aktivitas |
| --- | --- | --- |
| 1 | Admin | Membuka Kelola Loker → Perusahaan lalu memilih perusahaan. |
| 2 | Sistem | Menampilkan detail perusahaan, status package/langganan/trial, dan lowongan yang diposting. |
| 3 | Admin | Memilih lowongan untuk membuka daftar pelamar. |
| 4 | Sistem | Menampilkan Jobseeker yang melamar pada lowongan beserta tanggal apply. |
| 5 | Admin | Memilih pelamar untuk membuka data diri dan CV. |
| 6 | Sistem | Memeriksa role dan mencatat audit akses, lalu menampilkan data diri/CV secara terlindungi. |

# 8. Sequence Diagram

Sequence diagram menggambarkan urutan interaksi antar komponen, termasuk endpoint dan payload yang dipertukarkan.

## 8.1 Sequence: Registrasi Employer

![Diagram/gambar dari dokumen sumber](assets/image_09.png)

Gambar 9.1 Sequence Diagram — Registrasi Employer

Tabel interaksi:

| Step | Interaksi | Pesan / Aksi |
| --- | --- | --- |
| 1 | Employer → Aplikasi | Isi form registrasi lalu tekan "Buat akun" |
| 2 | Aplikasi → Server | POST /auth/employer/register { nama, telepon, email, password } |
| 3 | Server → Database | Cek email belum terdaftar dan validasi kata sandi |
| 4 | Server → Aplikasi | [Jika gagal] Return error spesifik |
| 5 | Server → Database | [Jika valid] INSERT user (password di-hash) |
| 6 | Server → Aplikasi | Return success + sesi login |
| 7 | Aplikasi → Employer | Arahkan ke Stepper Company Setup |

## 8.2 Sequence: Employer Post Job

![Diagram/gambar dari dokumen sumber](assets/image_03.png)

Gambar 9.2 Sequence Diagram — Employer Post Job

Tabel interaksi:

| Step | Interaksi | Pesan / Aksi |
| --- | --- | --- |
| 1 | Employer → Aplikasi | Memilih package Basic/Standard/Premium, lalu menyelesaikan pembayaran bila belum punya package aktif/kuota. |
| 2 | Aplikasi → Server | POST /employer/package-orders { package_id } |
| 3 | Server → Payment Gateway | Membuat transaksi dan menerima status pembayaran (gateway/metode pembayaran sesuai yang dipilih tim). |
| 4 | Server → Database | Setelah pembayaran terkonfirmasi, INSERT subscription dan kuota package serta masa aktifnya. |
| 5 | Employer → Aplikasi | Mengisi form Post Job dan menekan "Posting Loker". |
| 6 | Aplikasi → Server | POST /employer/jobs { data loker } |
| 7 | Server → Database | Validasi role, kepemilikan company, package aktif, tanggal berlaku, dan sisa kuota; jika lolos INSERT job dan kurangi kuota 1. |
| 8 | Server → Aplikasi | Return success { job_id, remaining_quota, expires_at } atau error package/kuota. |
| 9 | Aplikasi → Employer | Tampilkan loker di Dashboard dan website publik. |

## 8.3 Sequence: Admin Post Job atas Nama Perusahaan

![Diagram/gambar dari dokumen sumber](assets/image_27.png)

Gambar 9.3 Sequence Diagram — Admin Post Job atas Nama Perusahaan

Tabel interaksi:

| Step | Interaksi | Pesan / Aksi |
| --- | --- | --- |
| 1 | Admin → Aplikasi | Isi form Post Job pada panel CMS dan pilih perusahaan |
| 2 | Aplikasi → Server | POST /admin/jobs { company_id, data loker } |
| 3 | Server → Database | Validasi sesi dan role admin |
| 4 | Server → Database | INSERT job dengan posted_by = admin |
| 5 | Server → Aplikasi | Return success { job_id } |
| 6 | Aplikasi → Admin | Konfirmasi loker berhasil diposting |

## 8.4 Sequence: Admin Publikasi Artikel hingga Tampil di Blog

![Diagram/gambar dari dokumen sumber](assets/image_19.png)

Gambar 9.4 Sequence Diagram — Admin Publikasi Artikel hingga Tampil di Blog

Tabel interaksi:

| Step | Interaksi | Pesan / Aksi |
| --- | --- | --- |
| 1 | Admin → Aplikasi | Isi artikel lalu simpan/publikasikan |
| 2 | Aplikasi → Server | POST /admin/articles { judul, foto, isi } |
| 3 | Server → Database | Validasi role admin, INSERT article |
| 4 | Server → Aplikasi | Return success |
| 5 | Jobseeker → Aplikasi | Buka Menu Blog |
| 6 | Aplikasi → Server | GET /articles |
| 7 | Server → Database | SELECT artikel terpublikasi |
| 8 | Server → Aplikasi | Return daftar artikel (foto, tanggal, judul) |

## 8.5 Sequence: Jobseeker Mencari Loker

![Diagram/gambar dari dokumen sumber](assets/image_13.png)

Gambar 9.5 Sequence Diagram — Jobseeker Mencari Loker

Tabel interaksi:

| Step | Interaksi | Pesan / Aksi |
| --- | --- | --- |
| 1 | Jobseeker → Aplikasi | Isi kata kunci, lokasi, filter |
| 2 | Aplikasi → Server | GET /jobs?q=&location=&category=&job_type=&work_type=&sort= |
| 3 | Server → Database | SELECT loker sesuai kriteria |
| 4 | Server → Aplikasi | Return daftar loker |
| 5 | Jobseeker → Aplikasi | Klik card lowongan |
| 6 | Aplikasi → Server | GET /jobs/:id |
| 7 | Server → Aplikasi | Return detail loker |

## 8.6 Sequence: Jobseeker Melamar Lowongan (Apply)

![Diagram/gambar dari dokumen sumber](assets/image_28.png)

Gambar 9.6 Sequence Diagram — Jobseeker Melamar Lowongan (Apply)

Tabel interaksi:

| Step | Interaksi | Pesan / Aksi |
| --- | --- | --- |
| 1 | Jobseeker → Aplikasi | Tekan tombol "Apply" pada Job Detail |
| 2 | Aplikasi → Server | POST /jobs/:id/apply (dengan sesi login) |
| 3 | Server → Database | Validasi sesi dan role Jobseeker; cek profil dan CV sudah lengkap |
| 4 | Server → Database | Cek belum pernah melamar ke loker ini |
| 5 | Server → Aplikasi | [Jika belum login / profil belum lengkap / sudah melamar] Return error |
| 6 | Server → Database | [Jika valid] INSERT application (job_id, profile_id, applied_at, status) |
| 7 | Server → Aplikasi | Return success { application_id } |
| 8 | Aplikasi → Jobseeker | Tampilkan "Lamaran terkirim"; lamaran muncul di Lamaran Saya |

## 8.7 Sequence: Employer Melihat Pelamar dan CV

![Diagram/gambar dari dokumen sumber](assets/image_23.png)

Gambar 9.7 Sequence Diagram — Employer Melihat Pelamar dan CV

Tabel interaksi:

| Step | Interaksi | Pesan / Aksi |
| --- | --- | --- |
| 1 | Employer → Aplikasi | Buka menu Pelamar |
| 2 | Aplikasi → Server | GET /employer/applications?job_id= |
| 3 | Server → Database | Validasi role Employer; SELECT lamaran pada loker milik Employer |
| 4 | Server → Aplikasi | Return daftar pelamar (nama, posisi dilamar, tanggal apply) |
| 5 | Employer → Aplikasi | Pilih pelamar untuk melihat data diri dan CV |
| 6 | Aplikasi → Server | GET /employer/applications/:id |
| 7 | Server → Database | Validasi kepemilikan loker; SELECT data diri dan CV pelamar |
| 8 | Server → Aplikasi | Return data diri + URL CV sementara |

## 8.8 Sequence: Admin Melihat Pelamar dan Data Diri Jobseeker

![Diagram/gambar dari dokumen sumber](assets/image_16.png)

Gambar 9.8 Sequence Diagram — Admin Melihat Pelamar dan Data Diri Jobseeker

Tabel interaksi:

| Step | Interaksi | Pesan / Aksi |
| --- | --- | --- |
| 1 | Admin → Aplikasi | Pilih perusahaan dan lowongan pada Kelola Loker. |
| 2 | Aplikasi → Server | GET /admin/companies/:id/jobs/:job_id/applications |
| 3 | Server → Database | Validasi role Admin; SELECT application → job → company serta profil Jobseeker. |
| 4 | Server → Aplikasi | Return daftar pelamar (nama, posisi, tanggal apply). |
| 5 | Admin → Aplikasi | Pilih pelamar untuk melihat data diri/CV. |
| 6 | Aplikasi → Server | GET /admin/jobseekers/:id (application_id). |
| 7 | Server → Database | Validasi keterkaitan pelamar dengan perusahaan/lowongan, catat audit akses, ambil data diri dan referensi CV privat. |
| 8 | Server → Aplikasi | Return data diri dan URL CV sementara/terlindungi. |

# 9. Class Diagram

Field, tipe data, dan relasi pada diagram mengikuti ERD terbaru. Method merangkum perilaku yang dijelaskan pada PRD.

![Diagram/gambar dari dokumen sumber](assets/image_10.png)

Gambar 10.1 Class Diagram — Entitas Utama Start Work

![Diagram/gambar dari dokumen sumber](assets/image_05.png)

Gambar 10.2 Class Diagram — Data Diri Jobseeker

![Diagram/gambar dari dokumen sumber](assets/image_29.png)

Gambar 10.3 Class Diagram — Lamaran (Application)

![Diagram/gambar dari dokumen sumber](assets/image_20.png)

Gambar 10.4 Class Diagram — Package, Langganan, dan Pembayaran

Detail atribut dan method per class:

## Class: User

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| full_name | varchar |  |
| phone | varchar | null |
| email | varchar | unique, not null |
| email_verified_at | timestamp | null |
| password_hash | varchar | null |
| position | varchar |  |
| location | varchar |  |
| role | user_role | not null |
| created_at | timestamp |  |
| Method | register(), login(), loginWithGoogle(), logout(), resetPassword(), changePassword() | register(), login(), loginWithGoogle(), logout(), resetPassword(), changePassword() |
| Relasi | User 1 → 0..1 Company melalui companies.owner_user_id; User 1 → 1 JobseekerProfile; User 1 → banyak JobPost (posted_by) dan Article (author_id); Admin juga terkait relasi audit dan trial. | User 1 → 0..1 Company melalui companies.owner_user_id; User 1 → 1 JobseekerProfile; User 1 → banyak JobPost (posted_by) dan Article (author_id); Admin juga terkait relasi audit dan trial. |

## Class: JobseekerProfile

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| user_id | UUID | unique, not null; fk users.id |
| profile_photo | varchar | null |
| full_name | varchar |  |
| domicile | varchar |  |
| gender | varchar |  |
| birth_date | date |  |
| last_education | varchar |  |
| portfolio_link | varchar | null |
| cv_file | varchar |  |
| cv_filename | varchar | null |
| cv_uploaded_at | timestamp | null |
| Method | saveProfile(), uploadCV(), replaceCV() | saveProfile(), uploadCV(), replaceCV() |
| Relasi | JobseekerProfile 1 → banyak WorkExperience, OrganizationExperience, EducationHistory, Skill, Certificate, dan Application. | JobseekerProfile 1 → banyak WorkExperience, OrganizationExperience, EducationHistory, Skill, Certificate, dan Application. |

## Class: WorkExperience

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| profile_id | UUID | not null; fk jobseeker_profiles.id |
| job_type | varchar | kerja atau magang |
| job_title | varchar |  |
| company | varchar |  |
| start_date | date |  |
| end_date | date | null |
| key_responsibilities | text | null |

## Class: OrganizationExperience

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| profile_id | UUID | not null; fk jobseeker_profiles.id |
| organization_name | varchar |  |
| role | varchar |  |
| start_date | date | null |
| end_date | date | null |
| description | text | null |

## Class: EducationHistory

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| profile_id | UUID | not null; fk jobseeker_profiles.id |
| level | varchar |  |
| institution | varchar |  |
| major | varchar | null |
| start_date | date | null |
| end_date | date | null |

## Class: Skill

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| profile_id | UUID | not null; fk jobseeker_profiles.id |
| name | varchar |  |

## Class: Certificate

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| profile_id | UUID | not null; fk jobseeker_profiles.id |
| name | varchar |  |
| file_url | varchar | null |

## Class: Company

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| owner_user_id | UUID | unique, null; fk users.id |
| created_by | UUID | null; fk users.id |
| name | varchar |  |
| location | varchar |  |
| logo | varchar | null |
| website | varchar | null |
| contact_url | varchar | null |
| description | text | null |
| created_at | timestamp |  |
| Relasi | Company 1 → banyak JobPost dan CompanySubscription; owner_user_id menghubungkan perusahaan ke akun Employer. | Company 1 → banyak JobPost dan CompanySubscription; owner_user_id menghubungkan perusahaan ke akun Employer. |

## Class: JobPost

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| company_id | UUID | not null; fk companies.id |
| posted_by | UUID | not null; fk users.id |
| subscription_id | UUID | null; fk company_subscriptions.id |
| title | varchar |  |
| category | varchar |  |
| location | varchar |  |
| job_type | varchar |  |
| work_type | varchar |  |
| salary_min | decimal(15,2) | null |
| salary_max | decimal(15,2) | null |
| description | text |  |
| responsibilities | text | null |
| requirements | text | null |
| skills | text | null |
| benefits | text | null |
| status | job_post_status | default active |
| expires_at | timestamp | null |
| created_at | timestamp |  |
| Method | create(), update(), delete(), publish() | create(), update(), delete(), publish() |
| Relasi | JobPost 1 → banyak Application; subscription_id kosong untuk lowongan Admin yang tidak memakai kuota. | JobPost 1 → banyak Application; subscription_id kosong untuk lowongan Admin yang tidak memakai kuota. |
| Catatan | Index: (company_id, status) dan (status, expires_at). | Index: (company_id, status) dan (status, expires_at). |

## Class: Application

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| job_posts_id | UUID | not null; fk job_posts.id |
| profile_id | UUID | not null; fk jobseeker_profiles.id |
| applied_at | timestamp |  |
| status | application_status | default submitted |
| cv_file | varchar | null |
| Method | submit() | submit() |
| Relasi | Application menghubungkan satu JobPost dengan satu JobseekerProfile. | Application menghubungkan satu JobPost dengan satu JobseekerProfile. |
| Catatan | Unique (job_posts_id, profile_id); cv_file adalah referensi atau salinan CV saat apply. | Unique (job_posts_id, profile_id); cv_file adalah referensi atau salinan CV saat apply. |

## Class: Article

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| author_id | UUID | not null; fk users.id |
| title | varchar |  |
| cover_image | varchar | null |
| content | text |  |
| status | varchar |  |
| published_at | timestamp | null |
| Method | create(), update(), delete(), publish() | create(), update(), delete(), publish() |

## Class: Package

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| name | varchar | unique |
| quota | int |  |
| duration_days | int |  |
| price | decimal | not null |
| currency | varchar(3) | not null; default IDR |
| is_active | boolean |  |
| Method | getCatalog(), updateQuotaDuration() | getCatalog(), updateQuotaDuration() |
| Relasi | Package 1 → banyak CompanySubscription. | Package 1 → banyak CompanySubscription. |
| Catatan | Harga katalog disimpan pada kolom price (currency IDR): Basic Rp 99.000, Standard Rp 149.000, Premium Rp 239.000. | Harga katalog disimpan pada kolom price (currency IDR): Basic Rp 99.000, Standard Rp 149.000, Premium Rp 239.000. |

## Class: CompanySubscription

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| company_id | UUID | not null; fk companies.id |
| package_id | UUID | not null; fk packages.id |
| previous_subscription_id | UUID | null; fk company_subscriptions.id |
| source | subscription_source | default purchase |
| change_type | subscription_change_type | default new |
| status | subscription_status | default pending |
| quota_total | int |  |
| quota_used | int | default 0 |
| price_snapshot | decimal(12,2) | not null; default 0 |
| starts_at | timestamp | null |
| expires_at | timestamp | null |
| ended_at | timestamp | null |
| granted_by_admin_id | UUID | null; fk users.id |
| created_at | timestamp |  |
| Method | activate(), grantTrial(), consumeQuota(), getRemaining(), expire() | activate(), grantTrial(), consumeQuota(), getRemaining(), expire() |
| Relasi | CompanySubscription dimiliki satu Company dan satu Package; dapat terhubung ke subscription sebelumnya dan memiliki banyak PaymentTransaction. | CompanySubscription dimiliki satu Company dan satu Package; dapat terhubung ke subscription sebelumnya dan memiliki banyak PaymentTransaction. |
| Catatan | Upgrade, downgrade, perpanjangan, dan trial Admin direpresentasikan oleh source, change_type, previous_subscription_id, snapshot kuota, dan tanggal. | Upgrade, downgrade, perpanjangan, dan trial Admin direpresentasikan oleh source, change_type, previous_subscription_id, snapshot kuota, dan tanggal. |

## Class: PaymentTransaction

| Atribut | Tipe data | Constraint |
| --- | --- | --- |
| id | UUID | pk |
| subscription_id | UUID | not null; fk company_subscriptions.id |
| refund_of_id | UUID | null; fk payment_transactions.id |
| type | payment_type | default purchase |
| amount | decimal(12,2) |  |
| credit_applied | decimal | default 0 |
| currency | varchar(3) | null |
| status | payment_status | default pending |
| provider | varchar | null |
| provider_reference | varchar | null, unique |
| expires_at | timestamp | null |
| paid_at | timestamp | null |
| created_at | timestamp |  |
| Method | markPaid(), markFailed() | markPaid(), markFailed() |
| Relasi | PaymentTransaction dimiliki satu CompanySubscription; refund_of_id menautkan refund ke pembayaran asalnya. | PaymentTransaction dimiliki satu CompanySubscription; refund_of_id menautkan refund ke pembayaran asalnya. |
| Catatan | credit_applied adalah kredit upgrade, bukan nominal yang dibayarkan ke gateway. | credit_applied adalah kredit upgrade, bukan nominal yang dibayarkan ke gateway. |

# 10. Flowchart Alur Utama

Flowchart disajikan dalam bentuk tabel langkah. Tipe: Mulai/Selesai, Proses, Keputusan.

## 10.1 Flowchart: Alur Jobseeker Mencari Loker hingga Apply

| Step | Tipe | Aktivitas / Kondisi |
| --- | --- | --- |
| 1 | Mulai | Jobseeker membuka landing page |
| 2 | Proses | Isi pencarian jabatan/lokasi atau pilih chip populer |
| 3 | Proses | Tampilkan halaman Loker |
| 4 | Keputusan | Ada hasil sesuai? Tidak → ubah kata kunci/filter (kembali ke langkah 2) |
| 5 | Proses | Jobseeker membuka Job Detail (dari halaman Loker atau langsung dari card di landing) |
| 6 | Keputusan | Tertarik melamar? Tidak → kembali ke daftar |
| 7 | Proses | Tekan tombol Apply |
| 8 | Keputusan | Sudah login dan profil lengkap? Tidak → login atau lengkapi data diri, lalu kembali ke Job Detail |
| 9 | Proses | Sistem menyimpan lamaran |
| 10 | Selesai | Lamaran terkirim, tersimpan di Lamaran Saya, dan tampil di menu Pelamar Employer |

## 10.2 Flowchart: Alur Registrasi & Onboarding Employer

| Step | Tipe | Aktivitas / Kondisi |
| --- | --- | --- |
| 1 | Mulai | Employer membuka halaman login |
| 2 | Keputusan | Sudah punya akun? Ya → login (langkah 8) |
| 3 | Proses | Buka registrasi dan isi data |
| 4 | Keputusan | Data valid (kata sandi ≥ 8 karakter, sama dengan konfirmasi)? Tidak → tampilkan error, ulangi |
| 5 | Proses | Buat akun |
| 6 | Proses | Isi Company Setup (Stepper step 2) |
| 7 | Proses | Simpan & Lanjutkan |
| 8 | Selesai | Masuk ke Dashboard Employer |

## 10.3 Flowchart: Alur Admin Memposting Loker atas Nama Perusahaan

| Step | Tipe | Aktivitas / Kondisi |
| --- | --- | --- |
| 1 | Mulai | Admin login ke panel CMS |
| 2 | Proses | Buka menu Post Job |
| 3 | Keputusan | Perusahaan sudah terdaftar? Tidak → input data perusahaan baru |
| 4 | Proses | Pilih perusahaan |
| 5 | Proses | Isi form posting loker |
| 6 | Keputusan | Data valid? Tidak → tampilkan error, perbaiki |
| 7 | Proses | Simpan loker |
| 8 | Selesai | Loker tampil di halaman Loker publik |

## 10.4 Flowchart: Alur Admin Mempublikasikan Artikel

| Step | Tipe | Aktivitas / Kondisi |
| --- | --- | --- |
| 1 | Mulai | Admin membuka menu Artikel |
| 2 | Proses | Isi judul, foto, dan isi artikel |
| 3 | Keputusan | Data lengkap? Tidak → lengkapi |
| 4 | Proses | Simpan/publikasikan artikel |
| 5 | Proses | Artikel tampil sebagai card di Menu Blog |
| 6 | Selesai | Jobseeker dapat membuka halaman detail artikel |

## 10.5 Flowchart: Alur Employer Melihat Pelamar

| Step | Tipe | Aktivitas / Kondisi |
| --- | --- | --- |
| 1 | Mulai | Employer login dan membuka Dashboard |
| 2 | Proses | Buka menu Pelamar |
| 3 | Keputusan | Ada pelamar? Tidak → tampilkan informasi belum ada pelamar |
| 4 | Proses | Pilih pelamar dari daftar |
| 5 | Proses | Lihat data diri dan CV pelamar |
| 6 | Selesai | Employer selesai meninjau pelamar |

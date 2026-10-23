# Mulai di sini: uji endpoint StartWork

Dokumen ini berlaku untuk kode perbaikan tanggal 20 Oktober 2026. Jalankan semua perintah di **PowerShell IDE Antigravity**, satu blok demi satu blok. Tiga panduan lanjutan berada di folder yang sama: `ALUR TEST ENDPOIN JOBSEEKER.md`, `ALUR TEST ENDPOIN EMPLOYER.md`, dan `ALUR TEST ENDPOIN ADMIN INTERNAL.md`.

## 1. Keputusan yang dipakai

| Area | Aturan saat ini |
| --- | --- |
| Company admin | `owner_user_id` dan `created_by` adalah ID admin yang membuat. Admin boleh membuat beberapa company. |
| Company employer | `owner_user_id` dan `created_by` adalah ID employer. Satu employer hanya dapat membuat satu company. Company dengan nama sama milik admin tidak otomatis berpindah ke employer. |
| Job post | Employer mengelola lowongan yang ia buat pada company miliknya. Admin mengelola lowongan yang ia buat pada company miliknya. Admin dapat memantau semua company, lowongan, dan pelamar. |
| Application status | Hanya `submitted`, `in_process`, `accepted`, `rejected`. Status `viewed` dan `withdrawn` dihapus dari ENUM MySQL. |
| Berkas | Logo diunggah dari file JPG/JPEG/PNG dan disimpan pada disk publik. Foto jobseeker opsional, CV wajib sebelum apply; keduanya disimpan pada disk privat. |
| Apply admin | `apply_url` HTTP(S) atau `mailto:` dapat diisi untuk mengarahkan tombol **Apply Sekarang**. |
| Subscription/payment | **Ditunda sesuai keputusan PIC.** Tidak ada checkout atau payment gateway baru. Trial admin dikunci (`501`). Employer dapat membuat dan mengedit draft `inactive`; aktivasi tetap ditolak `409` jika belum ada subscription aktif. Jangan membuat baris pembayaran atau subscription palsu untuk melewati aturan ini. |
| Login Google | Route OAuth Google belum ada pada proyek ini. `email_verified_at` ditampilkan lewat `/api/me` sebagai data yang tersimpan; jangan menganggap kolom itu membuktikan login Google sebelum alur OAuth dibuat. |

## 2. Pasang perubahan dan migrasi

Proyek berada di `C:\laragon\www\startwork`. Paket perbaikan disiapkan sebagai berkas terpisah karena sesi ini hanya memiliki hak tulis di folder sementara. Instruksi pemasangan ada pada `INSTALL.md` dalam paket. Jalankan pemasangan terlebih dahulu, kemudian buka terminal PowerShell baru.

```powershell
$project = 'C:\laragon\www\startwork'
$php = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe'
Set-Location $project
& $php artisan route:list --path=api
& $php artisan migrate:status
& $php artisan migrate
& $php artisan storage:link
& $php artisan job-posts:expire
```

Sebelum `migrate`, buat cadangan database `startwork_db` di phpMyAdmin. Migrasi melepas indeks unik `companies.owner_user_id`, mengisi owner company lama yang dibuat admin dari `created_by`, dan membatasi ENUM `applications.status` ke empat status. Migrasi akan berhenti jika masih ada baris `viewed` atau `withdrawn`; periksa dan putuskan status penggantinya terlebih dahulu. Migrasi tambahan membuat tabel detail profil hanya jika belum ada, sehingga tabel yang sudah ada di `startwork_db` tidak ditimpa.

Jangan menjalankan `php artisan db:seed` setelah mengganti kata sandi akun demo. `DatabaseSeeder.php` saat ini mengubah ulang password akun demo menjadi nilai seed.

## 3. Variabel umum untuk setiap sesi tes

Jalankan blok ini di awal sesi PowerShell. Gunakan cookie berbeda untuk tiap role agar sesi tidak tercampur.

```powershell
$base = 'http://startwork.test'
$project = 'C:\laragon\www\startwork'
$php = 'C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64\php.exe'
$cookie = Join-Path $env:TEMP ('startwork-role-' + [guid]::NewGuid().ToString('N') + '.txt')
$responseFile = Join-Path $env:TEMP 'startwork-response.json'
Set-Location $project

$health = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' "$base/api"
"Health HTTP $health"
Get-Content -LiteralPath $responseFile -Raw
```

Harapan: HTTP `200`, `database: connected`. Gunakan **`/api` tanpa slash akhir**; `/api/` dapat dialihkan `301` oleh server.

## 4. Login yang menghasilkan sesi dan token CSRF baru

Ganti `$email` dan `$expectedRole`. Kata sandi diminta saat perintah berjalan. Setelah login HTTP `302`, token harus diambil lagi dari `/dashboard` karena Laravel meregenerasi sesi.

```powershell
$email = 'admin@startwork.test'
$expectedRole = 'admin'
$password = Read-Host "Password untuk $email"
$cookie = Join-Path $env:TEMP ('startwork-' + $expectedRole + '-' + [guid]::NewGuid().ToString('N') + '.txt')

$loginPage = curl.exe --noproxy '*' -sS -c $cookie -b $cookie "$base/login"
$loginToken = [regex]::Match(($loginPage -join "`n"), 'name="_token"\s+value="([^"]+)"').Groups[1].Value
if (-not $loginToken) { throw 'Token halaman login tidak ditemukan.' }

$loginHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -c $cookie -b $cookie -X POST "$base/login" `
  -H "X-CSRF-TOKEN: $loginToken" `
  --data-urlencode "email=$email" --data-urlencode "password=$password"
"Login HTTP $loginHttp"
if ($loginHttp -ne '302') { Get-Content -LiteralPath $responseFile -Raw; throw 'Login belum berhasil.' }

$dashboard = curl.exe --noproxy '*' -sS -c $cookie -b $cookie "$base/dashboard"
$csrf = [regex]::Match(($dashboard -join "`n"), 'name="csrf-token"\s+content="([^"]+)"').Groups[1].Value
if (-not $csrf) { throw 'Token sesi baru tidak ditemukan. Periksa login dan cookie.' }
$me = curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' "$base/api/me" | ConvertFrom-Json
$me.data | Format-List id, email, role, email_verified_at
if ($me.data.role -ne $expectedRole) { throw "Role tidak cocok: $($me.data.role)" }
```

Untuk logout, jalankan `curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' -X POST -b $cookie -c $cookie -H "X-CSRF-TOKEN: $csrf" "$base/logout"`. Harapan `302`. Setelah itu, login ulang dan ambil token baru; jangan pakai token lama.

## 5. Reset password akun yang dipilih

Atur `$targetEmail` dengan tepat. Jika `.env` memakai `MAIL_MAILER=log`, link reset muncul di `storage/logs/laravel.log`; jika memakai mailer lain, ambil dari kotak masuk yang sesuai. Reset tidak menghasilkan password otomatis: Anda memilih `$newPassword` sendiri.

```powershell
$targetEmail = 'admin@startwork.test'
$resetCookie = Join-Path $env:TEMP ('startwork-reset-' + [guid]::NewGuid().ToString('N') + '.txt')
$forgotPage = curl.exe --noproxy '*' -sS -c $resetCookie -b $resetCookie "$base/forgot-password"
$resetCsrf = [regex]::Match(($forgotPage -join "`n"), 'name="_token"\s+value="([^"]+)"').Groups[1].Value
if (-not $resetCsrf) { throw 'Token forgot-password tidak ditemukan.' }

$forgotHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -c $resetCookie -b $resetCookie -X POST "$base/forgot-password" `
  -H "X-CSRF-TOKEN: $resetCsrf" --data-urlencode "email=$targetEmail"
"Forgot HTTP $forgotHttp"
```

Harapan `302`. Temukan link untuk **email yang sama** dan **permintaan terbaru**. Contoh membaca log secara lokal:

```powershell
Get-Content -LiteralPath (Join-Path $project 'storage/logs/laravel.log') -Tail 160 |
  Select-String -Pattern 'reset-password/'
```

Lanjutkan setelah menyalin URL reset yang cocok:

```powershell
$resetLink = Read-Host 'Tempel URL reset-password lengkap dari email/log'
$resetToken = ([uri]$resetLink).AbsolutePath.Split('/')[-1]
$resetPage = curl.exe --noproxy '*' -sS -c $resetCookie -b $resetCookie $resetLink
$resetCsrf = [regex]::Match(($resetPage -join "`n"), 'name="_token"\s+value="([^"]+)"').Groups[1].Value
if (-not $resetToken -or -not $resetCsrf) { throw 'Token reset atau CSRF tidak ditemukan.' }
$newPassword = Read-Host 'Password baru (minimal 8 karakter)'
$resetHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -c $resetCookie -b $resetCookie -X POST "$base/reset-password" `
  -H "X-CSRF-TOKEN: $resetCsrf" `
  --data-urlencode "token=$resetToken" --data-urlencode "email=$targetEmail" `
  --data-urlencode "password=$newPassword" --data-urlencode "password_confirmation=$newPassword"
"Reset HTTP $resetHttp"
```

Harapan `302` menuju login. Buktikan hasilnya dengan **blok login pada langkah 4**, memakai `$email = $targetEmail` dan `$password = $newPassword`. HTTP `302` saja pada reset belum membuktikan password berubah; `/api/me` harus berhasil setelah login baru.

## 6. Makna kode respons

| Kode | Arti pada pengujian ini |
| --- | --- |
| 200/201/204 | Baca/ubah/buat/hapus berhasil sesuai endpoint. |
| 302 | Redirect login, logout, atau reset password; cek tahap berikutnya. |
| 401 | Belum login atau cookie tidak sesuai. |
| 403 | Sudah login tetapi role atau kepemilikan tidak memberi akses. |
| 404 | Data tidak ada dalam cakupan akun; termasuk lowongan draft pada endpoint publik. |
| 409 | Konflik status, lamaran ganda, lowongan punya pelamar, atau subscription employer belum tersedia. |
| 410 | Route lama yang tidak aman telah ditutup. |
| 419 | CSRF salah/kedaluwarsa. Ambil token baru dari `/dashboard` dengan cookie yang sama. |
| 422 | Validasi data gagal, misalnya CV atau skills belum ada. |
| 501 | Trial subscription sengaja ditunda. |

Untuk semua `POST`, `PUT`, dan `DELETE` setelah login, sertakan `-c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf"`. Jangan menyalin cookie atau token antar role.

# Alur uji endpoint Employer

Jalankan [00-MULAI-DI-SINI.md](00-MULAI-DI-SINI.md) langkah 2–3. Gunakan cookie khusus employer. Paket/subscription dan payment gateway **ditunda sesuai PIC**; panduan ini membuat dan mengelola draft `inactive`. Aktivasi tanpa subscription aktif memang menghasilkan `409`.

## 1. Register employer baru atau login employer lama

**Akun baru:** email harus belum ada di `users`. Route register employer mengisi role `employer` dan otomatis login.

```powershell
$cookie = Join-Path $env:TEMP ('startwork-employer-' + [guid]::NewGuid().ToString('N') + '.txt')
$newEmail = 'employer-baru-2@example.test'
$newPassword = Read-Host 'Password employer baru'
$registerPage = curl.exe --noproxy '*' -sS -c $cookie -b $cookie "$base/register"
$registerToken = [regex]::Match(($registerPage -join "`n"), 'name="_token"\s+value="([^"]+)"').Groups[1].Value
if (-not $registerToken) { throw 'Token register tidak ditemukan.' }
$registerHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/employer/register" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $registerToken" `
  --data-urlencode 'full_name=Employer Baru' --data-urlencode 'phone=081234567890' `
  --data-urlencode "email=$newEmail" --data-urlencode "password=$newPassword" `
  --data-urlencode "password_confirmation=$newPassword"
"Register HTTP $registerHttp"
Get-Content -LiteralPath $responseFile -Raw
```

Harapan `201`. Ambil token sesi **baru**:

```powershell
$dashboard = curl.exe --noproxy '*' -sS -c $cookie -b $cookie "$base/dashboard"
$csrf = [regex]::Match(($dashboard -join "`n"), 'name="csrf-token"\s+content="([^"]+)"').Groups[1].Value
if (-not $csrf) { throw 'Token sesi baru tidak ditemukan.' }
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' "$base/api/me"
```

**Akun lama:** gunakan blok login di panduan awal dengan `$cookie` di atas, `$email = 'employer-baru@example.test'` atau `'demo-employer@startwork.test'`, dan `$expectedRole = 'employer'`. Bila lupa password, jalankan blok reset pada panduan awal dengan `$targetEmail` yang persis sama; setelah reset, login ulang dengan password baru.

## 2. Baca atau setup company

```powershell
$companyHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -b $cookie -H 'Accept: application/json' "$base/api/employer/company"
"Company HTTP $companyHttp"
Get-Content -LiteralPath $responseFile -Raw
```

Jika `200`, company sudah ada dan **jangan POST lagi**. Jika `404`, buat company akun tersebut:

```powershell
$createCompanyHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/employer/company" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'name=PT Madju Bangsa' --data-urlencode 'location=Semarang' `
  --data-urlencode 'website=https://madju.example.test' `
  --data-urlencode 'description=Perusahaan uji employer'
"Create company HTTP $createCompanyHttp"
Get-Content -LiteralPath $responseFile -Raw
```

Harapan `201`. `owner_user_id` dan `created_by` berasal dari akun login, bukan input klien. Nama yang sama dengan company milik admin tetap menghasilkan company **terpisah**; tidak ada pengalihan otomatis.

Edit profil company tanpa mengubah kepemilikan:

```powershell
curl.exe --noproxy '*' -sS -X PUT "$base/api/employer/company" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'location=Jakarta' --data-urlencode 'description=Profil perusahaan diperbarui'
```

## 3. Upload logo company dari file

File harus JPG/JPEG/PNG, maksimal 2 MB. Kolom `logo` tidak menerima link URL pada POST/PUT company.

```powershell
$logoFile = Read-Host 'Path logo, contoh C:\Users\genji\Pictures\logo.png'
if (-not (Test-Path -LiteralPath $logoFile)) { throw 'File logo tidak ditemukan.' }
curl.exe --noproxy '*' -sS -X POST "$base/api/employer/company/logo" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  -F "logo=@$logoFile"
$company = curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/employer/company" | ConvertFrom-Json
$companyId = $company.data.id
"Company ID: $companyId; logo: $($company.data.logo)"
curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' "$base/api/companies/$companyId/logo"
```

Harapan upload `200`, logo tersimpan sebagai nama file, dan endpoint logo `200`. Jika logo `404`, jalankan `& $php artisan storage:link` dari folder proyek.

## 4. CREATE draft lowongan, satu per satu

Employer tidak mengirim `company_id`, `posted_by`, atau `subscription_id`; server menurunkannya dari sesi. `expires_at` diisi employer bila diperlukan dan tidak otomatis ditambah sebulan.

```powershell
$createJobHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/employer/job-posts" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'title=Backend Engineer' --data-urlencode 'category=Teknologi' `
  --data-urlencode 'location=Semarang' --data-urlencode 'job_type=Full-time' `
  --data-urlencode 'work_type=Hybrid' --data-urlencode 'salary_min=8000000' `
  --data-urlencode 'salary_max=12000000' `
  --data-urlencode 'description=Membangun API Laravel' `
  --data-urlencode 'responsibilities=Merancang dan menguji API' `
  --data-urlencode 'requirements=Pengalaman Laravel dan SQL' `
  --data-urlencode 'skills=Laravel, SQL' --data-urlencode 'benefits=BPJS' `
  --data-urlencode 'expires_at=2026-12-31 23:59:59'
"Create job HTTP $createJobHttp"
$createdJob = Get-Content -LiteralPath $responseFile -Raw | ConvertFrom-Json
$jobId = $createdJob.data.id
$createdJob.data | Format-List id, company_id, posted_by, status, expires_at
```

Harapan `201`, `status: inactive`, `company_id` sama dengan langkah 3. Untuk membuat lowongan kedua, jalankan blok CREATE sekali lagi dengan judul berbeda; tidak ada loop otomatis.

## 5. READ, UPDATE, DELETE draft

```powershell
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' "$base/api/employer/job-posts"
curl.exe --noproxy '*' -sS -X PUT "$base/api/employer/job-posts/$jobId" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'title=Senior Backend Engineer' `
  --data-urlencode 'category=Teknologi' --data-urlencode 'location=Jakarta' `
  --data-urlencode 'job_type=Full-time' --data-urlencode 'work_type=Remote' `
  --data-urlencode 'salary_min=10000000' --data-urlencode 'salary_max=15000000' `
  --data-urlencode 'description=API dan integrasi' `
  --data-urlencode 'responsibilities=Review kode' `
  --data-urlencode 'requirements=Laravel 3 tahun' `
  --data-urlencode 'skills=Laravel, SQL, Redis' `
  --data-urlencode 'benefits=BPJS dan bonus' `
  --data-urlencode 'expires_at=2027-01-15 23:59:59'
```

Harapan UPDATE `200`. `id`, `company_id`, `posted_by`, `subscription_id`, `status`, `inactive_reason`, `created_at`, dan `updated_at` tidak boleh dikirim pada update isi. Status memakai endpoint khusus.

Hapus **hanya draft uji yang tidak memiliki lamaran**. Simpan `$jobId` lain jika ingin melanjutkan uji aktivasi:

```powershell
$deleteId = Read-Host 'ID draft tanpa pelamar yang akan dihapus'
$deleteHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X DELETE "$base/api/employer/job-posts/$deleteId" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf"
"Delete HTTP $deleteHttp"
if ($deleteHttp -ne '204') { Get-Content -LiteralPath $responseFile -Raw }
```

Harapan `204`. Lowongan aktif harus diubah menjadi `inactive` dahulu; lowongan dengan lamaran mendapat `409` agar riwayat pelamar tidak hilang.

## 6. Aktivasi dan subscription: sengaja ditunda

```powershell
$activateHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X PUT "$base/api/employer/job-posts/$jobId/status" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'status=active'
"Aktivasi HTTP $activateHttp"
Get-Content -LiteralPath $responseFile -Raw
```

Jika `expires_at` di masa depan dan belum ada subscription aktif, harapan `409 Subscription aktif tidak ditemukan`. Ini hasil yang benar untuk fase ini. Katalog paket dapat dibaca melalui `GET /api/packages`, tetapi checkout, payment, kuota berbayar, dan idempotency pembayaran belum diuji karena ditunda PIC. Jangan membuat record `paid` secara manual untuk tes ini.

Untuk lowongan employer **yang sudah aktif dari data sebelumnya**, employer dapat menonaktifkan:

```powershell
$activeJobId = Read-Host 'ID lowongan employer yang sedang active'
curl.exe --noproxy '*' -sS -X PUT "$base/api/employer/job-posts/$activeJobId/status" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'status=inactive' `
  --data-urlencode 'inactive_reason=Kandidat yang cocok sudah ditemukan'
```

## 7. Lihat pelamar, CV, dan ubah status lamaran

Bagian ini memerlukan **lowongan employer milik akun login yang sudah active dan mempunyai pelamar**. Jika belum ada karena payment ditunda, lewati bagian ini secara tercatat; jangan memakai lamaran pada lowongan admin.

```powershell
$apps = curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/employer/applications" | ConvertFrom-Json
$apps.data | Select-Object application_id, applicant_name, job_title, status | Format-Table
$applicationId = Read-Host 'ID application milik lowongan employer ini'
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/employer/applications/$applicationId"
```

Daftar pelamar sekarang memakai pagination. `total` menunjukkan jumlah semua lamaran yang cocok dengan filter; `data` berisi satu halaman. Default 15 baris, `limit` maksimal 50. Bila `total` lebih besar daripada panjang `data`, baca halaman berikutnya:

```powershell
curl.exe --noproxy '*' -sS -G "$base/api/employer/applications" `
  -b $cookie -H 'Accept: application/json' `
  --data-urlencode 'limit=5' --data-urlencode 'page=2'

Unduh CV lewat endpoint privat. `-O -J` memakai nama file dari respons server, sehingga CV PDF tersimpan sebagai `.pdf` dan CV gambar tetap memakai ekstensi gambarnya. Simpan setiap percobaan di folder sementara baru agar file lama tidak tertimpa.

```powershell
$cvDir = Join-Path $env:TEMP ('startwork-cv-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $cvDir | Out-Null
$cvMeta = curl.exe --noproxy '*' -sS -b $cookie `
  --output-dir $cvDir -O -J -w '%{http_code}|%{filename_effective}' `
  "$base/api/employer/applications/$applicationId/cv"
$cvParts = $cvMeta -split '\|', 2
"CV HTTP $($cvParts[0])"
if ($cvParts[0] -ne '200') { throw 'Gagal mengunduh CV. Periksa role, kepemilikan lowongan, ID application, dan file CV.' }
$cvFile = Join-Path $cvDir (Split-Path -Leaf $cvParts[1])
if (-not (Test-Path -LiteralPath $cvFile)) { throw 'File CV tidak ditemukan setelah curl.exe selesai.' }
"CV tersimpan: $cvFile"
Invoke-Item -LiteralPath $cvFile
```

Jika file yang diunggah pelamar adalah PDF, aplikasi pembaca PDF akan terbuka. Jangan membuka berkas biner di editor teks. Untuk CV lama yang path-nya tidak ada secara fisik, endpoint memberi `404`; pelamar perlu mengunggah ulang lalu melamar ke lowongan baru jika diperlukan. Nama CV baru tidak mengganti CV pada lamaran lama.


Contoh perpindahan pertama `submitted` → `in_process`:

```powershell
curl.exe --noproxy '*' -sS -X PUT "$base/api/employer/applications/$applicationId/status" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'status=in_process'
```

Untuk lamaran lain yang masih `submitted`, ganti `$applicationId` dan jalankan perintah terpisah dengan `status=accepted` atau `status=rejected`. Dari `in_process` boleh menuju `accepted` atau `rejected`. Status akhir tidak dapat diubah lagi; `viewed` dan `withdrawn` tidak diterima. Employer lain mendapat `404` untuk lamaran, CV, dan lowongan yang bukan miliknya.

## 8. Logout employer sebelum login admin

Jalankan dengan `$cookie` dan `$csrf` milik employer yang masih login:

```powershell
$logoutHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/logout" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf"
"Logout HTTP $logoutHttp"
if ($logoutHttp -ne '302') { Get-Content -LiteralPath $responseFile -Raw; throw 'Logout gagal.' }

$afterLogoutHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -b $cookie -H 'Accept: application/json' "$base/api/me"
"Cek sesi setelah logout HTTP $afterLogoutHttp"
if ($afterLogoutHttp -ne '401') { Get-Content -LiteralPath $responseFile -Raw; throw 'Sesi belum berakhir.' }
Remove-Variable csrf, loginToken, password -ErrorAction SilentlyContinue
```

Pastikan hasilnya `302` lalu `401`. Untuk login admin, buat sesi baru dengan email admin dan `$expectedRole = 'admin'`; cookie dan CSRF employer tidak dipakai lagi.
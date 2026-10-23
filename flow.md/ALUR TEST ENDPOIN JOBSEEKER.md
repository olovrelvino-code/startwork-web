# Alur uji endpoint Jobseeker

Jalankan [00-MULAI-DI-SINI.md](00-MULAI-DI-SINI.md) langkah 2–3 terlebih dahulu. Semua contoh memakai `curl.exe` di PowerShell. Uji satu blok, baca hasilnya, lalu lanjut. Gunakan cookie khusus jobseeker.

## 1. Register jobseeker baru atau login akun lama

**Akun baru:** route `/register` mengisi `users.role = jobseeker`. Ganti alamat email dengan yang belum terdaftar.

```powershell
$cookie = Join-Path $env:TEMP ('startwork-jobseeker-' + [guid]::NewGuid().ToString('N') + '.txt')
$newEmail = 'jobseeker-baru@example.test'
$newPassword = Read-Host 'Password akun baru'
$registerPage = curl.exe --noproxy '*' -sS -c $cookie -b $cookie "$base/register"
$registerToken = [regex]::Match(($registerPage -join "`n"), 'name="_token"\s+value="([^"]+)"').Groups[1].Value
if (-not $registerToken) { throw 'Token register tidak ditemukan.' }
$registerHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -c $cookie -b $cookie -X POST "$base/register" `
  -H "X-CSRF-TOKEN: $registerToken" `
  --data-urlencode 'name=Jobseeker Baru' --data-urlencode "email=$newEmail" `
  --data-urlencode "password=$newPassword" --data-urlencode "password_confirmation=$newPassword"
"Register HTTP $registerHttp"
```

Harapan `302`. Ambil token sesi baru dan identitas:

```powershell
$dashboard = curl.exe --noproxy '*' -sS -c $cookie -b $cookie "$base/dashboard"
$csrf = [regex]::Match(($dashboard -join "`n"), 'name="csrf-token"\s+content="([^"]+)"').Groups[1].Value
if (-not $csrf) { throw 'Token dashboard tidak ditemukan.' }
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' "$base/api/me"
```

**Akun lama:** atur `$cookie` seperti di atas, lalu gunakan blok login pada panduan awal dengan `$email = 'demo-jobseeker@startwork.test'` dan `$expectedRole = 'jobseeker'`. Jangan memakai password seed jika akun itu pernah direset. Untuk lupa kata sandi, gunakan langkah 5 panduan awal dengan `$targetEmail` yang tepat.

`email_verified_at` dapat dilihat dari `/api/me`. Route Google OAuth belum ada; jangan mengisi `email_verified_at` secara manual untuk menguji login Google.

## 2. Lihat, filter, urutkan, dan baca lowongan

```powershell
curl.exe --noproxy '*' -sS "$base/api/job-posts"
curl.exe --noproxy '*' -sS -G "$base/api/job-posts" `
  --data-urlencode 'category=Teknologi' --data-urlencode 'location=Jakarta' `
  --data-urlencode 'work_type=Hybrid' --data-urlencode 'job_type=Full-time' `
  --data-urlencode 'q=Developer' --data-urlencode 'sort=latest'
```

Nilai `sort`: `latest`, `oldest`, `a-z`, `z-a`. Filter boleh dijalankan sendiri-sendiri. Endpoint publik hanya menampilkan lowongan `active` yang belum kedaluwarsa.

Daftar lowongan memakai pagination. `total` adalah jumlah semua lowongan yang cocok dengan filter, `data` hanya berisi halaman saat ini. `limit` boleh 1–50 (default 15); `page` dimulai dari 1. Contoh melihat dua halaman:

```powershell
curl.exe --noproxy '*' -sS -G "$base/api/job-posts" `
  --data-urlencode 'limit=5' --data-urlencode 'page=1'
curl.exe --noproxy '*' -sS -G "$base/api/job-posts" `
  --data-urlencode 'limit=5' --data-urlencode 'page=2'
```

Untuk melihat lowongan pada **company tertentu**, cari company, pilih `id` dari hasilnya, lalu baca `open_positions`. Ganti `q=Formulatrix` dengan nama company yang ingin Anda uji. Nama company bisa sama, jadi pilih berdasarkan ID. Lowongan dengan `apply_url` kosong adalah lowongan **internal** yang cocok untuk tes apply pada langkah 3 dan 7.

```powershell
$companies = curl.exe --noproxy '*' -sS -G "$base/api/companies" `
  --data-urlencode 'q=Formulatrix' | ConvertFrom-Json
$companies.data | Select-Object id, name, location | Format-Table

$companyId = Read-Host 'ID company dari tabel di atas'
$company = curl.exe --noproxy '*' -sS "$base/api/companies/$companyId" | ConvertFrom-Json
$company.data | Format-List id, name, location
$company.open_positions | Select-Object id, title, apply_url, expires_at | Format-Table

$jobId = Read-Host 'ID lowongan internal (apply_url kosong) dari open_positions'
$detail = curl.exe --noproxy '*' -sS "$base/api/job-posts/$jobId" | ConvertFrom-Json
if ($detail.data.company_id -ne $companyId) { throw 'Lowongan bukan milik company yang dipilih.' }
if ($detail.data.apply_url) { throw 'Lowongan ini memakai pendaftaran eksternal; pilih yang apply_url kosong.' }
$detail.data | Format-List id, title, company_name, description, apply_url, status
```

Jika `open_positions` kosong, company tersebut belum mempunyai lowongan aktif yang belum kedaluwarsa. Jika admin membuat lowongan dengan `apply_url`, lihat tombol pada `$base/jobs/$jobId` dan redirect dari `$base/api/job-posts/$jobId/apply-now`; lowongan itu tidak menerima `POST /apply` internal.

## 3. Buktikan profil wajib sebelum apply

```powershell
$applyHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/job-posts/$jobId/apply" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf"
"Apply sebelum profil HTTP $applyHttp"
Get-Content -LiteralPath $responseFile -Raw
```

Harapan `422` jika profil, skills, atau CV belum lengkap. Jika akun lama sudah lengkap dan pernah melamar, `409` berarti duplikat; pilih lowongan lain.

## 4. Buat atau edit data utama profil

Endpoint memakai ID akun dari sesi; **jangan mengirim `user_id`**. Foto profil opsional. Tanggal memakai `YYYY-MM-DD`.

```powershell
$profileHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/jobseeker/profile" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'full_name=Jobseeker Baru' `
  --data-urlencode 'domicile=Semarang' --data-urlencode 'birth_date=2000-01-01' `
  --data-urlencode 'gender=Perempuan' --data-urlencode 'last_education=S1' `
  --data-urlencode 'portfolio_link=https://portfolio.example.test'
"Profile HTTP $profileHttp"
Get-Content -LiteralPath $responseFile -Raw
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' "$base/api/jobseeker/profile"
```

Harapan `201` untuk baru atau `200` untuk edit. `cv_file` tidak diterima sebagai teks/path dari klien; unggah file pada langkah 6.

## 5. Isi skills wajib dan riwayat opsional

Endpoint `POST/PUT /api/jobseeker/profile/details` **mengganti seluruh daftar**. Sertakan lagi bagian yang ingin dipertahankan saat edit. `skills` wajib minimal satu; empat bagian lain boleh kosong.

```powershell
$detailsHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/jobseeker/profile/details" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'skills[0]=SQL' --data-urlencode 'skills[1]=Laravel' `
  --data-urlencode 'work_experiences[0][job_type]=Magang' `
  --data-urlencode 'work_experiences[0][job_title]=Data Analyst Intern' `
  --data-urlencode 'work_experiences[0][company]=PT Contoh' `
  --data-urlencode 'work_experiences[0][start_date]=2025-01-01' `
  --data-urlencode 'organization_experiences[0][organization_name]=Himpunan Mahasiswa' `
  --data-urlencode 'organization_experiences[0][role]=Anggota' `
  --data-urlencode 'education_histories[0][level]=S1' `
  --data-urlencode 'education_histories[0][institution]=Universitas Contoh' `
  --data-urlencode 'certificates[0][name]=Sertifikat SQL'
"Details HTTP $detailsHttp"
Get-Content -LiteralPath $responseFile -Raw
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' "$base/api/jobseeker/profile/details"
```

Harapan `200` dan `complete: true`. Untuk edit, ulangi blok dengan `-X PUT` dan isi daftar lengkap terbaru. Untuk menghapus satu bagian opsional, gunakan `DELETE /api/jobseeker/profile/details/work_experiences` (atau `organization_experiences`, `education_histories`, `certificates`). Skills tidak dapat dihapus seluruhnya.

## 6. Unggah foto opsional dan CV wajib

Gunakan **file lokal** `.jpg`, `.jpeg`, `.png` untuk foto (maks. 2 MB), serta `.pdf`, `.jpg`, `.jpeg`, `.png` untuk CV (maks. 5 MB). Tidak memakai URL gambar. Berkas privat hanya dapat dibaca melalui endpoint yang memeriksa hak akses.

```powershell
$photoFile = Read-Host 'Path file foto, contoh C:\Users\genji\Pictures\foto.png (kosongkan bila tidak perlu)'
if ($photoFile) {
  if (-not (Test-Path -LiteralPath $photoFile)) { throw 'File foto tidak ditemukan.' }
  curl.exe --noproxy '*' -sS -X POST "$base/api/jobseeker/profile/photo" `
    -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
    -F "photo=@$photoFile"
}
$cvFile = Read-Host 'Path file CV, contoh C:\Users\genji\Documents\cv.pdf'
if (-not (Test-Path -LiteralPath $cvFile)) { throw 'File CV tidak ditemukan.' }
curl.exe --noproxy '*' -sS -X POST "$base/api/jobseeker/profile/cv" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  -F "cv=@$cvFile"
```

Periksa `cv_uploaded_at` pada `GET /api/jobseeker/profile`. File CV lama pada database yang tidak ada secara fisik harus diunggah ulang; teks path lama tidak cukup untuk apply.

## 7. Apply sekali dan lihat status

```powershell
$applyHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/job-posts/$jobId/apply" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf"
"Apply HTTP $applyHttp"
Get-Content -LiteralPath $responseFile -Raw
```

Harapan `201`, status awal `submitted`. Jalankan perintah yang sama lagi: harapan `409` karena satu jobseeker hanya boleh melamar sekali pada lowongan yang sama.

```powershell
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/jobseeker/my-applications"
```

Status berikutnya hanya dapat diubah oleh employer pemilik lowongan. Akun jobseeker tidak bisa membaca lamaran akun lain atau mengubah status. Jika muncul `419`, ambil token baru dari `/dashboard` dengan cookie jobseeker yang sama seperti pada panduan awal.

## 8. Logout jobseeker sebelum login employer

Jalankan dengan `$cookie` dan `$csrf` milik jobseeker yang masih login:

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

Pastikan hasilnya `302` lalu `401`. Setelah itu buka panduan employer, gunakan email employer dan `$expectedRole = 'employer'` pada blok login awal. Jangan gunakan cookie atau token jobseeker untuk employer.
# Alur uji endpoint Admin Internal

Jalankan [00-MULAI-DI-SINI.md](00-MULAI-DI-SINI.md) langkah 2–3 terlebih dahulu. Admin internal **tidak memiliki route register publik**; akun demo disediakan oleh `DatabaseSeeder.php`. Jangan menjalankan seeder ulang setelah reset password karena seeder mengubah password demo. Semua perintah di bawah menggunakan `curl.exe` di PowerShell.

## 1. Login atau reset password admin

```powershell
$cookie = Join-Path $env:TEMP ('startwork-admin-' + [guid]::NewGuid().ToString('N') + '.txt')
$email = 'admin@startwork.test'
$expectedRole = 'admin'
```

Jalankan blok **login** pada panduan awal. Jika password lupa, jalankan blok **reset password** dengan `$targetEmail = $email`, lalu login dengan password baru. Pastikan `/api/me` menampilkan `role: admin`. Login `302` harus diikuti token **baru** dari `/dashboard`; token sebelum login menyebabkan `419` pada POST/PUT/DELETE.

## 2. Pantau dan cari company yang terdaftar

Endpoint admin menampilkan `owner_user_id` dan `created_by`; endpoint publik `/api/companies` sengaja hanya menampilkan data publik.

```powershell
$companies = curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/companies" | ConvertFrom-Json
$companies.data | Select-Object id, name, owner_user_id, created_by | Format-Table

$search = curl.exe --noproxy '*' -sS -G "$base/api/admin/companies" `
  -b $cookie -H 'Accept: application/json' `
  --data-urlencode 'q=Formulatrix' | ConvertFrom-Json
$search.data | Select-Object id, name, owner_user_id, created_by | Format-Table
```

Pilih ID dari output, bukan mengira nama selalu unik. Company bernama sama dapat dimiliki admin dan employer secara terpisah.

```powershell
$companyId = Read-Host 'ID company yang ingin diperiksa'
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/companies/$companyId"
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/companies/$companyId/job-posts"
```

Harapan `200`. Detail berisi company dan seluruh lowongan company, termasuk `active`, `inactive`, `expired`.

## 3. Pantau pelamar menurut nama dan company

```powershell
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/applicants"
curl.exe --noproxy '*' -sS -G "$base/api/admin/applicants" `
  -b $cookie -H 'Accept: application/json' --data-urlencode 'name=Siti'
curl.exe --noproxy '*' -sS -G "$base/api/admin/applicants" `
  -b $cookie -H 'Accept: application/json' --data-urlencode "company_id=$companyId"
```

`total: 0` berarti tidak ada pelamar yang cocok, bukan error. Output memuat `applicant_name`, `company_name`, `job_title`, dan status. Untuk CV pelamar yang tersedia secara fisik di disk privat:

```powershell
$applicationId = Read-Host 'ID application dari daftar pelamar'
$cvDir = Join-Path $env:TEMP ('startwork-admin-cv-' + [guid]::NewGuid().ToString('N'))
New-Item -ItemType Directory -Path $cvDir | Out-Null
$cvMeta = curl.exe --noproxy '*' -sS -b $cookie `
  --output-dir $cvDir -O -J -w '%{http_code}|%{filename_effective}' `
  "$base/api/admin/applicants/$applicationId/cv"
$cvParts = $cvMeta -split '\|', 2
"CV HTTP $($cvParts[0])"
if ($cvParts[0] -ne '200') { throw 'CV tidak dapat diunduh. Periksa ID application dan file CV.' }
$cvFile = Join-Path $cvDir (Split-Path -Leaf $cvParts[1])
if (-not (Test-Path -LiteralPath $cvFile)) { throw 'File CV tidak ditemukan setelah curl.exe selesai.' }
"CV tersimpan: $cvFile"
Invoke-Item -LiteralPath $cvFile
```

Harapan `200`; `404` bila application atau file CV tidak ada. Nama file berasal dari lamaran saat dibuat, bukan nama CV profil terbaru. Admin dapat melihat pelamar di semua company untuk fungsi monitoring.

Jika `total` lebih besar daripada jumlah baris dalam `data`, lanjutkan ke halaman berikutnya. Filter nama dan company dapat dipakai bersama pagination:

```powershell
curl.exe --noproxy '*' -sS -G "$base/api/admin/applicants" `
  -b $cookie -H 'Accept: application/json' `
  --data-urlencode 'name=Siti' --data-urlencode "company_id=$companyId" `
  --data-urlencode 'limit=5' --data-urlencode 'page=2'
```

`total` adalah seluruh lamaran yang cocok dengan filter; `data` hanya halaman yang diminta. Default 15 baris, `limit` maksimal 50.

## 4. Buat company milik admin

Company admin tidak berhubungan dengan alur company employer. Kolom `owner_user_id` dan `created_by` harus sama dengan ID admin yang login. Isi logo melalui upload pada langkah berikutnya.

```powershell
$createCompanyHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/admin/companies" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'name=Formulatrix Admin' `
  --data-urlencode 'location=Semarang' `
  --data-urlencode 'website=https://formulatrix.example.test' `
  --data-urlencode 'description=Company untuk lowongan admin'
"Create company HTTP $createCompanyHttp"
if ($createCompanyHttp -ne '201') { Get-Content -LiteralPath $responseFile -Raw; throw 'Company gagal dibuat.' }
$newCompany = Get-Content -LiteralPath $responseFile -Raw | ConvertFrom-Json
$adminCompanyId = $newCompany.data.id
$newCompany.data | Format-List id, name, owner_user_id, created_by
```

Harapan kedua ID kepemilikan sama dengan `/api/me`. Admin dapat mengedit company miliknya:

```powershell
curl.exe --noproxy '*' -sS -X PUT "$base/api/admin/companies/$adminCompanyId" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'location=Semarang Timur' `
  --data-urlencode 'description=Deskripsi perusahaan diperbarui'
```

Admin dapat memantau company employer lewat GET tetapi tidak mengubah company employer melalui route admin ini; update/hapus company dibatasi pada company miliknya.

## 5. Upload logo dari file JPG/JPEG/PNG

```powershell
$logoFile = Read-Host 'Path file logo, contoh C:\Users\genji\Pictures\fmlx.png'
if (-not (Test-Path -LiteralPath $logoFile)) { throw 'File logo tidak ditemukan.' }
$logoHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/admin/companies/$adminCompanyId/logo" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  -F "logo=@$logoFile"
"Logo HTTP $logoHttp"
Get-Content -LiteralPath $responseFile -Raw
curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  "$base/api/companies/$adminCompanyId/logo"
```

Harapan upload `200` dan lihat logo `200`. Maksimal 2 MB. Database menyimpan **nama file**, bukan URL gambar. Untuk logo `404`, pastikan `& $php artisan storage:link` sudah dijalankan.

## 6. Buat lowongan admin dengan seluruh kolom

Admin memilih `company_id` **miliknya**. `posted_by` otomatis ID admin dan `subscription_id` kosong. `apply_url` opsional; bila diisi, tombol **Apply Sekarang** membawa pelamar ke situs/form/email pendaftaran eksternal.

```powershell
$createJobHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/admin/job-posts" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode "company_id=$adminCompanyId" `
  --data-urlencode 'title=QA Engineer' --data-urlencode 'category=Teknologi' `
  --data-urlencode 'location=Semarang' --data-urlencode 'job_type=Full-time' `
  --data-urlencode 'work_type=On-site' --data-urlencode 'salary_min=8000000' `
  --data-urlencode 'salary_max=12000000' `
  --data-urlencode 'description=Menguji aplikasi' `
  --data-urlencode 'responsibilities=Menulis test case' `
  --data-urlencode 'requirements=Pengalaman QA' `
  --data-urlencode 'skills=Testing, SQL' --data-urlencode 'benefits=BPJS' `
  --data-urlencode 'expires_at=2026-12-31 23:59:59' `
  --data-urlencode 'apply_url=https://forms.example.test/apply'
"Create job HTTP $createJobHttp"
if ($createJobHttp -ne '201') { Get-Content -LiteralPath $responseFile -Raw; throw 'Job post gagal dibuat.' }
$newJob = Get-Content -LiteralPath $responseFile -Raw | ConvertFrom-Json
$adminJobId = $newJob.data.id
$newJob.data | Format-List id, company_id, posted_by, status, apply_url
```

Harapan `201`, `status: active`, `apply_url` tersimpan. Untuk lowongan yang menerima lamaran **internal**, buat lowongan lain dengan perintah CREATE tersendiri dan **hilangkan baris `--data-urlencode 'apply_url=...'`**. Jobseeker harus memakai lowongan internal untuk tes `POST /apply`.

## 7. Baca job, preview Apply Sekarang, dan edit isi

```powershell
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/job-posts?mine=1"
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/companies/$adminCompanyId/job-posts"
curl.exe --noproxy '*' -sS "$base/api/job-posts/$adminJobId"
curl.exe --noproxy '*' -sS "$base/jobs/$adminJobId" | Select-String 'Apply Sekarang'
curl.exe --noproxy '*' -sS -o $responseFile -w '%{redirect_url} %{http_code}' `
  "$base/api/job-posts/$adminJobId/apply-now"
```

Harapan redirect `302` menuju `apply_url`. Jangan memakai `-L` jika ingin melihat redirect tanpa membuka situs tujuan.

```powershell
curl.exe --noproxy '*' -sS -X PUT "$base/api/admin/job-posts/$adminJobId" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'title=Senior QA Engineer' `
  --data-urlencode 'category=Teknologi' --data-urlencode 'location=Jakarta' `
  --data-urlencode 'job_type=Full-time' --data-urlencode 'work_type=Hybrid' `
  --data-urlencode 'salary_min=10000000' --data-urlencode 'salary_max=15000000' `
  --data-urlencode 'description=Menjaga kualitas aplikasi' `
  --data-urlencode 'responsibilities=Automasi test' `
  --data-urlencode 'requirements=Pengalaman 3 tahun' `
  --data-urlencode 'skills=Testing, SQL, Playwright' `
  --data-urlencode 'benefits=BPJS dan bonus' `
  --data-urlencode 'expires_at=2027-01-15 23:59:59' `
  --data-urlencode 'apply_url=mailto:hr@example.test'
```

Harapan `200`. Jangan mengirim `id`, `company_id`, `posted_by`, `subscription_id`, `created_at`, `updated_at`, atau `status` lewat update isi. Status memakai route berikutnya.

## 8. Ubah status dan cek expired

```powershell
curl.exe --noproxy '*' -sS -X PUT "$base/api/admin/job-posts/$adminJobId/status" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'status=inactive' `
  --data-urlencode 'inactive_reason=Kandidat yang cocok sudah ditemukan'
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/job-posts?status=inactive"
```

Aktifkan lagi hanya jika `expires_at` berada di masa depan:

```powershell
curl.exe --noproxy '*' -sS -X PUT "$base/api/admin/job-posts/$adminJobId/status" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'status=active'
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/job-posts?status=active"
```

`expired` dipicu saat `expires_at` lewat. Untuk memproses yang sudah jatuh tempo dan memeriksa hasilnya:

```powershell
& $php artisan job-posts:expire
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' `
  "$base/api/admin/job-posts?status=expired"
```

Jangan mengubah tanggal produksi hanya untuk tes. Scheduler Laravel menjalankan `job-posts:expire` setiap menit **jika scheduler sedang dijalankan**; perintah manual di atas aman untuk cek saat ini.

## 9. Hapus job uji dan company kosong

Job harus `inactive` atau `expired` **dan tidak punya application**. Untuk job aktif, lakukan langkah nonaktifkan dulu. Company hanya bisa dihapus setelah semua job miliknya hilang dan tanpa riwayat subscription.

```powershell
$deleteJobId = Read-Host 'ID job admin tanpa pelamar yang akan dihapus'
$deleteJobHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X DELETE "$base/api/admin/job-posts/$deleteJobId" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf"
"Delete job HTTP $deleteJobHttp"
if ($deleteJobHttp -ne '204') { Get-Content -LiteralPath $responseFile -Raw }
```

Jika company sudah kosong dan memang company uji, hapus dengan `DELETE /api/admin/companies/$adminCompanyId` memakai cookie/token yang sama. Harapan `204`; `409` melindungi company dengan job atau riwayat subscription.

## 10. Buat, lihat, edit, dan hapus artikel

```powershell
$articleHttp = curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X POST "$base/api/admin/articles" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'title=Tips Karier QA' `
  --data-urlencode 'content=Persiapkan portofolio dan latihan wawancara.' `
  --data-urlencode 'status=draft'
"Create article HTTP $articleHttp"
$article = Get-Content -LiteralPath $responseFile -Raw | ConvertFrom-Json
$articleId = $article.data.id
curl.exe --noproxy '*' -sS -b $cookie -H 'Accept: application/json' "$base/api/admin/articles"
curl.exe --noproxy '*' -sS -X PUT "$base/api/admin/articles/$articleId" `
  -c $cookie -b $cookie -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf" `
  --data-urlencode 'status=published'
curl.exe --noproxy '*' -sS "$base/api/articles"
curl.exe --noproxy '*' -sS "$base/api/articles/$articleId"
```

Harapan artikel `draft` hanya terlihat di daftar admin; setelah `published`, tampil di daftar publik. `author_id` selalu diambil dari sesi. Hapus artikel uji:

```powershell
curl.exe --noproxy '*' -sS -o $responseFile -w '%{http_code}' `
  -X DELETE "$base/api/admin/articles/$articleId" -c $cookie -b $cookie `
  -H 'Accept: application/json' -H "X-CSRF-TOKEN: $csrf"
```

## 11. Batas akses dan area yang ditunda

Admin dapat melihat semua company/job/pelamar, tetapi UPDATE/DELETE company dan job melalui route admin hanya untuk data yang dibuat admin pada company admin. Employer tidak dapat mengakses route admin. Route publik untuk membuat company, menghapus job, dan membaca pelamar tanpa login sudah ditutup.

Katalog `GET /api/packages` masih dapat dibaca. `POST /api/admin/companies/{id}/grant-trial` memberi `501` karena subscription dan payment gateway ditunda PIC. Jangan menguji pembayaran atau mengisi tabel `payment_transactions` secara manual dalam alur ini.

## 12. Logout admin

Jalankan dengan `$cookie` dan `$csrf` milik admin yang masih login:

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

Pastikan hasilnya `302` lalu `401`. Jika akan mengulang rangkaian tes dari jobseeker, jalankan blok login dengan cookie baru, email jobseeker, dan `$expectedRole = 'jobseeker'`.
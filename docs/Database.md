// ============================ ENUM ============================
Enum user_role {
  jobseeker
  employer
  admin
}

Enum job_post_status {
  active
  inactive
  expired
}

Enum application_status {
  submitted
  in_process
  accepted
  rejected
}

Enum article_status {
  draft
  published
}

Enum subscription_status {
  pending
  scheduled
  active
  expired
  cancelled
  replaced
}

Enum subscription_source {
  purchase
  admin_trial
}

Enum subscription_change_type {
  new
  renewal
  upgrade
  downgrade
  trial
}

Enum payment_status {
  pending
  paid
  failed
  expired
  refunded
}

Enum payment_type {
  purchase
  upgrade
  renewal
  refund
}

//ENTITAS
Table users {
  id uuid [pk]
  full_name varchar
  phone varchar [null, note: 'boleh kosong: akun Google tidak membawa nomor telepon']
  email varchar [unique, not null]
  email_verified_at timestamp [null]
  password_hash varchar [null, note: 'kosong untuk akun yang hanya dibuat lewat Google']
  position varchar
  location varchar
  role user_role [not null]
  created_at timestamp
}

Table jobseeker_profiles {
  id uuid [pk]
  user_id uuid [not null, unique]
  profile_photo varchar [null]
  full_name varchar
  domicile varchar
  birth_date date
  gender varchar
  last_education varchar
  portfolio_link varchar [null]
  cv_file varchar
  cv_filename varchar [null]
  cv_uploaded_at timestamp [null]
}

Table work_experiences {
  id uuid [pk]
  profile_id uuid [not null]
  job_type varchar [note: 'Kerja atau magang']
  job_title varchar
  company varchar
  start_date date
  end_date date [null]
  key_responsibilities text [null]
}

Table organization_experiences {
  id uuid [pk]
  profile_id uuid [not null]
  organization_name varchar
  role varchar
  start_date date [null]
  end_date date [null]
  description text [null]
}

Table education_histories {
  id uuid [pk]
  profile_id uuid [not null]
  level varchar
  institution varchar
  major varchar [null]
  start_date date [null]
  end_date date [null]
}

Table skills {
  id uuid [pk]
  profile_id uuid [not null]
  name varchar
}

Table certificates {
  id uuid [pk]
  profile_id uuid [not null]
  name varchar
  file_url varchar [null]
}

Table companies {
  id uuid [pk]
  owner_user_id uuid [null, note: 'Akun Admin atau Employer pemilik perusahaan; Admin dapat memiliki banyak company']
  created_by uuid [null, note: 'User Employer atau Admin yang membuat data perusahaan']
  name varchar
  location varchar
  logo varchar [null]
  website varchar [null]
  contact_url varchar [null]
  description text [null]
  created_at timestamp
}

Table job_posts {
  id uuid [pk]
  company_id uuid [not null]
  posted_by uuid [not null, note: 'User Employer atau Admin yang memposting']
  subscription_id uuid [null, note: 'Subscription yang kuotanya dipakai; kosong untuk loker Admin yang tidak memakai kuota']
  title varchar
  category varchar
  location varchar
  job_type varchar
  work_type varchar
  salary_min decimal(15,2) [null]
  salary_max decimal(15,2) [null]
  description text
  responsibilities text [null]
  requirements text [null]
  skills text [null]
  benefits text [null]
  status job_post_status [not null, default: 'active']
  expires_at timestamp [null, note: 'Akhir masa tayang loker']
  created_at timestamp

  indexes {
    (company_id, status)
    (status, expires_at)
  }
}

Table applications {
  id uuid [pk]
  job_posts_id uuid [not null]
  profile_id uuid [not null]
  applied_at timestamp
  status application_status [not null, default: 'submitted']
  cv_file varchar [null, note: 'Referensi atau salinan CV saat melamar']

  indexes {
    (job_posts_id, profile_id) [unique, name: 'one_application_per_jobseeker_per_job']
  }
}

Table articles {
  id uuid [pk]
  author_id uuid [not null]
  title varchar
  cover_image varchar [null]
  content text
  status article_status [not null, default: 'draft']
  published_at timestamp [null]
}

Table packages {
  id uuid [pk]
  name varchar [unique, note: 'Basic, Standard, atau Premium']
  quota int
  duration_days int
  price decimal [not null]
  currency varchar(3) [not null, default: 'IDR']
  is_active boolean
}

Table company_subscriptions {
  id uuid [pk]
  company_id uuid [not null]
  package_id uuid [not null]
  previous_subscription_id uuid [null, note: 'Subscription sebelumnya pada upgrade, downgrade, atau perpanjangan']
  source subscription_source [not null, default: 'purchase']
  change_type subscription_change_type [not null, default: 'new']
  status subscription_status [not null, default: 'pending']
  quota_total int [note: 'Salinan kuota package saat dibeli']
  quota_used int [default: 0]
  price_snapshot decimal(12,2) [not null, default: 0, note: 'Harga katalog saat dibeli; 0 untuk admin_trial; dasar hitung kredit upgrade']
  starts_at timestamp [null, note: 'Kosong selama pending']
  expires_at timestamp [null]
  ended_at timestamp [null, note: 'Diisi saat replaced atau cancelled']
  granted_by_admin_id uuid [null, note: 'Admin pemberi trial, bila trial']
  created_at timestamp

  indexes {
    (company_id, status)
  }

}

Table payment_transactions {
  id uuid [pk]
  subscription_id uuid [not null]
  refund_of_id uuid [null, note: 'Diisi pada baris refund; menunjuk pembayaran asli']
  type payment_type [not null, default: 'purchase']
  amount decimal(12,2) [note: 'Nominal yang benar-benar dibayar ke gateway, setelah kredit']
  credit_applied decimal(12,2) [default: 0, note: 'Potongan kredit upgrade; bukan uang lewat gateway']
  currency varchar(3) [null]
  status payment_status [not null, default: 'pending']
  provider varchar [null, note: 'Gateway/metode pembayaran belum ditentukan']
  provider_reference varchar [null, unique]
  expires_at timestamp [null, note: 'Batas waktu bayar']
  paid_at timestamp [null]
  created_at timestamp
}


//RELASI ERD
// Akun dan profil Jobseeker
Ref: jobseeker_profiles.user_id - users.id
Ref: work_experiences.profile_id > jobseeker_profiles.id
Ref: organization_experiences.profile_id > jobseeker_profiles.id
Ref: education_histories.profile_id > jobseeker_profiles.id
Ref: skills.profile_id > jobseeker_profiles.id
Ref: certificates.profile_id > jobseeker_profiles.id

// Employer, perusahaan, dan lowongan
Ref: companies.owner_user_id - users.id
Ref: companies.created_by >? users.id
Ref: job_posts.company_id > companies.id
Ref: job_posts.posted_by > users.id
Ref: job_posts.subscription_id >? company_subscriptions.id

// Lamaran menghubungkan Jobseeker dengan lowongan
Ref: applications.job_posts_id > job_posts.id
Ref: applications.profile_id > jobseeker_profiles.id

// Artikel
Ref: articles.author_id > users.id

// Package, langganan, dan pembayaran
Ref: company_subscriptions.company_id > companies.id
Ref: company_subscriptions.package_id > packages.id
Ref: company_subscriptions.previous_subscription_id >? company_subscriptions.id
Ref: company_subscriptions.granted_by_admin_id >? users.id
Ref: payment_transactions.subscription_id > company_subscriptions.id
Ref: payment_transactions.refund_of_id >? payment_transactions.id

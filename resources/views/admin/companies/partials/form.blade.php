@php($editing = isset($company))
<form data-admin-form data-method="{{ $editing ? 'PUT' : 'POST' }}" data-success-url="{{ $editing ? route('admin.companies.show', $company->id) : url('/admin/companies/{id}') }}" data-success-message="Perusahaan berhasil {{ $editing ? 'diperbarui' : 'dibuat' }}." action="{{ $editing ? url('/api/admin/companies/'.$company->id) : url('/api/admin/companies') }}" class="grid gap-4 sm:grid-cols-2">
    @csrf
    <div>
        <label class="admin-label" for="company-name">Nama perusahaan</label>
        <input class="admin-input" id="company-name" name="name" required maxlength="255" value="{{ $company->name ?? '' }}">
    </div>
    <div>
        <label class="admin-label" for="company-location">Lokasi</label>
        <input class="admin-input" id="company-location" name="location" required maxlength="255" value="{{ $company->location ?? '' }}">
    </div>
    <div>
        <label class="admin-label" for="company-website">Website resmi</label>
        <input class="admin-input" id="company-website" name="website" type="url" value="{{ $company->website ?? '' }}">
    </div>
    <div>
        <label class="admin-label" for="company-contact">Tautan kontak</label>
        <input class="admin-input" id="company-contact" name="contact_url" type="url" value="{{ $company->contact_url ?? '' }}">
    </div>
    <div class="sm:col-span-2">
        <label class="admin-label" for="company-description">Deskripsi</label>
        <textarea class="admin-input min-h-28" id="company-description" name="description">{{ $company->description ?? '' }}</textarea>
    </div>
    <div class="sm:col-span-2"><button type="submit" class="admin-button-primary">{{ $editing ? 'Simpan perubahan' : 'Buat perusahaan' }}</button></div>
</form>

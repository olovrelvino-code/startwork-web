@props(['current' => null])
@php($selected = $current ?: 'Others')
<div>
    <label class="admin-label" for="job-category">Kategori</label>
    <select class="admin-input" id="job-category" name="category" required>
        @if (! in_array($selected, \App\JobCategory::PREDEFINED, true))
            <option value="{{ $selected }}" selected hidden>{{ $selected }} (kategori tersimpan)</option>
        @endif
        @foreach (\App\JobCategory::PREDEFINED as $category)
            <option value="{{ $category }}" @selected($selected === $category)>{{ $category }}</option>
        @endforeach
    </select>
    @if (! in_array($selected, \App\JobCategory::PREDEFINED, true))
        <p class="mt-1 text-xs text-slate-500">Kategori lama tetap tersimpan sampai Anda memilih kategori baru.</p>
    @endif
</div>

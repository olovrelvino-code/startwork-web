@props(['name', 'label', 'accept' => '.jpg,.jpeg,.png', 'help' => 'JPG, JPEG, atau PNG. Maksimal 2 MB.'])
<div>
    <label for="{{ $name }}" class="admin-label">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="file" accept="{{ $accept }}" data-admin-image-input {{ $attributes->merge(['class' => 'admin-input file:mr-4 file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-medium']) }}>
    <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    <img data-admin-preview class="mt-3 hidden h-24 w-24 rounded-lg border border-slate-200 object-contain" alt="Pratinjau gambar terpilih">
</div>

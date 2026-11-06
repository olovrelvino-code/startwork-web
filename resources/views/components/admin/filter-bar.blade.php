@props(['action'])
<form method="GET" action="{{ $action }}" {{ $attributes->merge(['class' => 'mb-5 flex flex-col gap-3 sm:flex-row sm:items-end']) }}>
    {{ $slot }}
    <button type="submit" class="admin-button-secondary">Terapkan</button>
</form>

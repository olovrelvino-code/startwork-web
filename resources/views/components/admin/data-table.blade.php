<div {{ $attributes->merge(['class' => 'admin-panel admin-data-surface admin-data-table overflow-hidden']) }}>
    @isset($mobile)<div class="admin-data-table-mobile divide-y divide-slate-100 md:hidden">{{ $mobile }}</div>@endisset
    <div class="{{ isset($mobile) ? 'hidden overflow-x-auto md:block' : 'overflow-x-auto' }}">{{ $slot }}</div>
</div>

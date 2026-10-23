<div {{ $attributes->merge(['class' => 'admin-panel overflow-hidden']) }}>
    @isset($mobile)<div class="divide-y divide-slate-100 md:hidden">{{ $mobile }}</div>@endisset
    <div class="{{ isset($mobile) ? 'hidden md:block' : 'overflow-x-auto' }}">{{ $slot }}</div>
</div>

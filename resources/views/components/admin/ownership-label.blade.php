@props(['ownerId', 'ownerRole' => null])
@php($owned = $ownerId === auth()->id())
<span {{ $attributes->merge(['class' => 'inline-flex whitespace-nowrap rounded-md px-2 py-1 text-xs font-medium '.($owned ? 'bg-[#FFF0E3] text-[#8F4108]' : 'bg-slate-100 text-slate-600')]) }}>{{ $owned ? 'Milik Admin' : ($ownerRole === 'admin' ? 'Admin lain' : 'Milik Employer') }}</span>

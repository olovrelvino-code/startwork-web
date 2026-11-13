@extends('layouts.employer')

@section('title', 'Langganan')
@section('section_label', 'Langganan')

@section('content')
    <section data-subscription data-packages-url="{{ url('/api/packages') }}" aria-labelledby="subscription-title">
        <div class="mb-8 max-w-2xl">
            <p class="text-sm font-semibold text-[#B85B12]">Paket StartWork</p>
            <h1 id="subscription-title" class="mt-2 text-2xl font-semibold tracking-tight text-[#17394B] sm:text-3xl">Langganan</h1>
            <p class="mt-3 text-base leading-relaxed text-slate-600">Pilih dukungan rekrutmen yang sesuai dengan kebutuhan perusahaan Anda.</p>
        </div>

        <p data-package-status role="status" aria-live="polite" class="mb-5 text-sm text-slate-600">Memuat paket…</p>
        <div data-package-list class="grid items-stretch gap-5 md:grid-cols-2 xl:grid-cols-3 xl:gap-6"></div>

        <template data-package-template>
            <article class="flex h-full min-w-0 flex-col rounded-xl border border-slate-200 border-t-4 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-md sm:p-7">
                <div class="flex min-h-7 items-start justify-between gap-3">
                    <h2 data-package-name class="text-2xl font-semibold tracking-tight text-[#17394B]"></h2>
                    <span data-package-badge class="hidden rounded-full bg-[#FFF2E3] px-3 py-1 text-xs font-semibold text-[#8F460F]">Most Popular</span>
                </div>
                <p data-package-price class="admin-number mt-5 break-words text-3xl font-bold tracking-tight text-[#17394B]"></p>
                <dl class="mt-5 grid grid-cols-2 gap-4 border-y border-slate-100 py-4 text-sm">
                    <div><dt class="text-slate-500">Kuota</dt><dd data-package-quota class="mt-1 font-semibold text-[#17394B]"></dd></div>
                    <div><dt class="text-slate-500">Durasi</dt><dd data-package-duration class="mt-1 font-semibold text-[#17394B]"></dd></div>
                </dl>
                <p class="mt-6 text-sm font-semibold text-[#17394B]">Benefit paket</p>
                <ul data-package-benefits class="mt-3 flex-1 space-y-3 text-sm leading-relaxed text-slate-700"></ul>
                <button type="button" data-select-package class="mt-8 w-full rounded-lg bg-[#B85B12] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#95420D] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Pilih Paket</button>
            </article>
        </template>
    </section>

    <dialog data-package-dialog aria-labelledby="package-dialog-title" aria-describedby="package-dialog-note" class="w-[min(92vw,30rem)] rounded-xl border border-slate-200 bg-white p-0 text-[#17394B] shadow-xl backdrop:bg-slate-950/60">
        <div class="p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4"><h2 id="package-dialog-title" class="text-2xl font-semibold">Konfirmasi Paket</h2><button type="button" data-package-close aria-label="Tutup konfirmasi" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-[#17394B]">✕</button></div>
            <p data-confirm-name class="mt-6 text-xl font-semibold"></p>
            <p data-confirm-price class="admin-number mt-2 text-3xl font-bold text-[#B85B12]"></p>
            <dl class="mt-6 grid grid-cols-2 gap-4 rounded-lg bg-[#F3F7F8] p-4 text-sm"><div><dt class="text-slate-500">Kuota</dt><dd data-confirm-quota class="mt-1 font-semibold"></dd></div><div><dt class="text-slate-500">Durasi</dt><dd data-confirm-duration class="mt-1 font-semibold"></dd></div></dl>
            <p id="package-dialog-note" class="mt-5 text-sm leading-relaxed text-slate-600">Pembelian belum tersedia. Konfirmasi ini tidak mengubah akses akun.</p>
            <button type="button" data-package-confirm class="mt-6 w-full rounded-lg bg-[#B85B12] px-4 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#95420D] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Konfirmasi pilihan</button>
        </div>
    </dialog>
@endsection

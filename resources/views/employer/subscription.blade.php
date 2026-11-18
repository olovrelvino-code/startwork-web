@extends('layouts.employer')

@section('title', 'Langganan')
@section('section_label', 'Langganan')

@section('content')
    <section data-subscription data-packages-url="{{ url('/api/packages') }}" data-availability-url="{{ route('employer.subscription.purchases.availability') }}" data-purchase-url="{{ route('employer.subscription.purchases.store') }}" data-company-name="{{ $companyName }}" aria-labelledby="subscription-title">
        <div class="mb-8 max-w-2xl">
            <p class="text-sm font-semibold text-[#B85B12]">Paket StartWork</p>
            <h1 id="subscription-title" class="mt-2 text-2xl font-semibold tracking-tight text-[#17394B] sm:text-3xl">Pilihan Paket Langganan</h1>
            <p class="mt-3 text-base leading-relaxed text-slate-600">Pilih dukungan rekrutmen yang sesuai dengan kebutuhan perusahaan Anda. Transaksi diproses transparan melalui verifikasi manual admin.</p>
        </div>

        <p data-package-status role="status" aria-live="polite" class="mb-5 text-sm text-slate-600">Memuat paket…</p>
        <div data-package-list class="grid items-stretch gap-6 md:grid-cols-2 xl:grid-cols-3"></div>

        <div class="mt-12 rounded-2xl border border-slate-200 bg-[#F3F7F8] p-6 sm:flex sm:items-center sm:justify-between sm:gap-6 shadow-sm">
            <div>
                <span class="inline-flex rounded-full bg-[#17394B]/10 px-3 py-1 text-xs font-bold text-[#17394B]">Riwayat Pesanan</span>
                <h2 class="mt-2 text-lg font-bold text-[#17394B]">Kelola & Cek Status Pembelian Anda</h2>
                <p class="mt-1 text-sm text-slate-600">Ingin mengirim bukti pembayaran, mengecek status verifikasi admin, atau membatalkan pesanan? Kunjungi menu Riwayat Pembelian.</p>
            </div>
            <a href="{{ route('employer.subscription.history') }}" class="mt-4 inline-flex shrink-0 items-center gap-2 rounded-xl bg-[#17394B] px-5 py-3 text-xs font-bold text-white shadow-sm hover:bg-[#21546F] sm:mt-0 transition-all hover:shadow-md">
                <span>Buka Riwayat Pembelian</span>
                <span aria-hidden="true">→</span>
            </a>
        </div>

        <template data-package-template>
            <article class="group relative flex h-full min-w-0 flex-col rounded-2xl border border-slate-200/90 border-t-4 border-t-[#17394B] bg-white p-6 shadow-sm transition-all duration-300 hover:-translate-y-2 hover:border-[#21546F] hover:shadow-xl sm:p-7">
                <div class="flex min-h-7 items-start justify-between gap-3">
                    <div>
                        <span class="inline-flex rounded-md bg-[#F3F7F8] px-2.5 py-1 text-xs font-bold text-[#17394B]">Paket Rekrutmen</span>
                        <h2 data-package-name class="mt-3 text-2xl font-bold tracking-tight text-[#17394B]"></h2>
                    </div>
                </div>

                <div class="mt-5">
                    <p data-package-price class="admin-number break-words text-3xl font-extrabold tracking-tight text-[#DE7C23]"></p>
                    <span class="text-xs text-slate-500">Masa berlaku aktif sesuai paket yang dipilih</span>
                </div>

                <dl class="mt-5 grid grid-cols-2 gap-3 rounded-xl border border-slate-100 bg-[#F8FAFC] p-3.5 text-sm">
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Kuota Lowongan</dt>
                        <dd data-package-quota class="mt-1 font-bold text-[#17394B]"></dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium text-slate-500">Durasi Aktif</dt>
                        <dd data-package-duration class="mt-1 font-bold text-[#17394B]"></dd>
                    </div>
                </dl>

                <div class="mt-6 flex-1">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Benefit Paket:</p>
                    <ul data-package-benefits class="mt-3 space-y-2.5 text-sm leading-relaxed text-slate-700"></ul>
                </div>

                <button type="button" data-select-package class="mt-8 flex w-full items-center justify-center gap-2 rounded-xl bg-[#B85B12] px-4 py-3.5 text-sm font-bold text-white shadow-sm transition-all duration-200 hover:bg-[#95420D] hover:shadow-md active:scale-[0.99] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">
                    <span>Pilih Paket</span>
                    <span aria-hidden="true">→</span>
                </button>
            </article>
        </template>
    </section>

    <dialog data-package-dialog aria-labelledby="package-dialog-title" aria-describedby="package-dialog-note" class="w-[min(92vw,30rem)] rounded-xl border border-slate-200 bg-white p-0 text-[#17394B] shadow-xl backdrop:bg-slate-950/60">
        <div class="p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4"><h2 id="package-dialog-title" class="text-2xl font-semibold">Konfirmasi Paket</h2><button type="button" data-package-close aria-label="Tutup konfirmasi" class="rounded p-1 text-slate-500 hover:bg-slate-100 hover:text-[#17394B]">✕</button></div>
            <p data-confirm-name class="mt-6 text-xl font-semibold"></p>
            <p data-confirm-price class="admin-number mt-2 text-3xl font-bold text-[#B85B12]"></p>
            <dl class="mt-6 grid grid-cols-2 gap-4 rounded-lg bg-[#F3F7F8] p-4 text-sm"><div><dt class="text-slate-500">Kuota</dt><dd data-confirm-quota class="mt-1 font-semibold"></dd></div><div><dt class="text-slate-500">Durasi</dt><dd data-confirm-duration class="mt-1 font-semibold"></dd></div></dl>
            <p class="mt-6 text-sm font-semibold">Benefit paket</p><ul data-confirm-benefits class="mt-3 space-y-2 text-sm leading-relaxed text-slate-700"></ul>
            <p id="package-dialog-note" class="mt-5 text-sm leading-relaxed text-slate-600">Tim StartWork akan membantu proses langganan melalui WhatsApp. Aktivasi dilakukan manual oleh admin.</p>
            <div class="mt-6 flex gap-3"><button type="button" data-package-close class="flex-1 rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold hover:bg-slate-50">Batal</button><button data-package-confirm type="button" class="flex-1 rounded-lg bg-[#B85B12] px-4 py-3 text-center text-sm font-semibold text-white transition-colors hover:bg-[#95420D] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Konfirmasi</button></div>
        </div>
    </dialog>
    <dialog data-trial-queue-dialog aria-labelledby="trial-queue-title" aria-describedby="trial-queue-message" class="w-[min(92vw,30rem)] rounded-xl border border-slate-200 bg-white p-0 text-[#17394B] shadow-xl backdrop:bg-slate-950/60">
        <div class="p-6 sm:p-8">
            <div class="mb-5 flex h-11 w-11 items-center justify-center rounded-full bg-amber-50 text-[#B85B12]"><svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg></div>
            <h2 id="trial-queue-title" class="text-xl font-semibold">Pembelian Belum Dapat Dilanjutkan</h2>
            <p id="trial-queue-message" data-trial-queue-message class="mt-4 text-sm leading-relaxed text-slate-700"></p>
            <button type="button" data-trial-queue-close class="mt-6 w-full rounded-lg bg-[#B85B12] px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-[#95420D] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#B85B12]">Mengerti</button>
        </div>
    </dialog>
@endsection

@props(['title', 'message', 'endpoint', 'successUrl', 'buttonLabel' => 'Hapus', 'confirmLabel' => 'Ya, hapus'])
<div x-data="{ open: false }" class="inline-flex">
    <button type="button" class="admin-button-quiet text-rose-700" @click="open = true">{{ $buttonLabel }}</button>
    <div x-cloak x-show="open" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/60 p-4" @keydown.escape.window="open = false" role="presentation">
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl" role="dialog" aria-modal="true" aria-label="{{ $title }}" @click.outside="open = false">
            <h2 class="text-lg font-semibold text-slate-900">{{ $title }}</h2>
            <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $message }}</p>
            <form class="mt-6 flex justify-end gap-2" data-admin-form data-method="DELETE" data-success-url="{{ $successUrl }}" action="{{ $endpoint }}">
                @csrf
                <button type="button" class="admin-button-secondary" @click="open = false">Batal</button>
                <button type="submit" class="admin-button bg-rose-700 text-white hover:bg-rose-800">{{ $confirmLabel }}</button>
            </form>
        </div>
    </div>
</div>

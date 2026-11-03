@props([
    'items' => [],
    'name' => 'accordion',
])

<div {{ $attributes->merge(['class' => 'space-y-3']) }}>
    @foreach ($items as $item)
        <details name="{{ $name }}" class="jobseeker-faq rounded-xl border border-slate-200 bg-white p-5">
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 font-semibold text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--jobseeker-accent)]">
                <span>{{ $item['question'] ?? $item['title'] ?? '' }}</span>
                <span class="jobseeker-faq-indicator text-xl font-normal leading-none text-slate-400" aria-hidden="true">+</span>
            </summary>
            <div class="pt-3 text-sm leading-7 text-slate-600">
                {!! nl2br(e($item['answer'] ?? $item['content'] ?? '')) !!}
            </div>
        </details>
    @endforeach
</div>


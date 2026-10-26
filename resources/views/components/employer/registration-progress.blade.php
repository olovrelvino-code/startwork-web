@props(['step', 'variant' => 'light'])

<ol {{ $attributes->class(['registration-progress', 'registration-progress--dark' => $variant === 'dark']) }} aria-label="Progres registrasi Employer">
    @foreach ([1 => ['Buat akun', 'Buat kredensial Employer'], 2 => ['Profil perusahaan', 'Lengkapi informasi perusahaan']] as $number => [$label, $description])
        @php($state = $number < $step ? 'complete' : ($number === $step ? 'active' : 'upcoming'))
        <li class="registration-progress__item" data-state="{{ $state }}" @if ($state === 'active') aria-current="step" @endif>
            <span class="registration-progress__marker" aria-hidden="true">{{ $state === 'complete' ? '✓' : $number }}</span>
            <span class="registration-progress__text">
                <span class="registration-progress__caption">{{ $state === 'complete' ? 'Selesai' : ($state === 'active' ? 'Sedang berlangsung' : 'Berikutnya') }}</span>
                <strong class="registration-progress__label">{{ $label }}</strong>
                @if ($variant === 'dark')
                    <span class="registration-progress__description">{{ $description }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>

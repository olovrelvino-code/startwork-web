<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Akun Jobseeker · StartWork</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="jobseeker-shell min-h-screen bg-slate-50 text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen max-w-6xl flex-col px-4 sm:px-6">
        <header class="jobseeker-auth-header flex items-center justify-between border-b border-slate-200 py-5"><a href="{{ url('/') }}" class="text-xl font-extrabold tracking-tight focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600"><span class="jobseeker-wordmark-start">Start</span><span class="jobseeker-wordmark-work">Work</span></a><a href="{{ route('jobs.index') }}" class="text-sm font-semibold text-slate-700 hover:text-blue-700">Jelajahi lowongan</a></header>
        <main class="grid flex-1 items-center gap-12 py-10 lg:grid-cols-[minmax(0,1fr)_minmax(360px,440px)]">
            <div class="hidden max-w-xl lg:block"><p class="text-sm font-semibold text-blue-700">Ruang Jobseeker</p><h1 class="mt-4 text-4xl font-bold leading-tight tracking-tight">Satu tempat untuk peluang dan perjalanan karier Anda.</h1><p class="mt-5 text-base leading-7 text-slate-600">Temukan lowongan aktif, siapkan profil dan CV, lalu pantau status lamaran yang Anda kirim.</p></div>
            <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">{{ $slot }}</div>
        </main>
    </div>
</body>
</html>

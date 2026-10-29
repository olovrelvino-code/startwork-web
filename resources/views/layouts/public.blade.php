<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ trim($__env->yieldContent('description', 'Artikel dan informasi karier dari StartWork.')) }}">
    <title>{{ trim($__env->yieldContent('title', 'Artikel')) }} · StartWork</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#f7f8fa] text-[#172b42] antialiased">
    <header class="border-b border-slate-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-5 py-4 sm:px-8">
            <a href="{{ url('/') }}" class="text-xl font-extrabold tracking-tight text-[#172b42] focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#be6332]">Start<span class="text-[#be6332]">Work</span></a>
            <nav aria-label="Navigasi utama" class="flex items-center gap-5 text-sm font-semibold">
                <a href="{{ route('articles.index') }}" class="text-[#172b42] hover:text-[#a54d25] focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#be6332]">Artikel</a>
                <a href="{{ url('/') }}" class="text-slate-600 hover:text-[#a54d25] focus-visible:rounded-sm focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-[#be6332]">Beranda</a>
            </nav>
        </div>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="mt-16 border-t border-slate-200 bg-white px-5 py-6 text-center text-sm text-slate-500">© {{ date('Y') }} StartWork</footer>
</body>
</html>

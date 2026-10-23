<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Internal') · Start Work</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/admin.js'])
</head>
<body class="admin-shell antialiased">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[70] focus:bg-white focus:p-3">Lewati navigasi</a>
    <div x-data="{ menuOpen: false }" class="min-h-screen lg:flex">
        <div x-cloak x-show="menuOpen" x-transition.opacity class="fixed inset-0 z-40 bg-slate-950/60 lg:hidden" @click="menuOpen = false" aria-hidden="true"></div>
        <x-admin.navigation />
        <div class="min-w-0 flex-1">
            <x-admin.topbar />
            <main id="main-content" class="p-0 lg:p-2 lg:pl-0">
                <div class="admin-sheet px-4 pb-12 pt-7 sm:px-6 lg:px-8 lg:pt-8">
                    <div id="admin-feedback" aria-live="polite" class="mb-5 hidden"></div>
                    @if (session('success'))
                        <x-admin.notice type="success" :message="session('success')" />
                    @endif
                    @yield('content')
                </div>
            </main>
        </div>
    </div>
</body>
</html>

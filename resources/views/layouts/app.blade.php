<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- atau jika pakai PNG -->
    <link rel="icon" href="{{ asset('favicon.png') }}" type="image/png">

    <title>@yield('title', config('app.name', 'Dtrack'))</title>

    <!-- Load Vite (Bootstrap, JS, dll) -->
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    {{-- Tambahan CSS dari halaman --}}
    @yield('styles')
</head>
<body>
    <div id="app">
        <!-- Header Navbar -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="{{ url('/') }}">
                    <img src="{{ asset('storage/images/logo.png') }}" alt="My Logo" height="40">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
                        aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarContent">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('dashboard') ? 'active fw-bold' : '' }}" href="{{ url('/dashboard') }}">
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ request()->is('histori') ? 'active fw-bold' : '' }}" href="{{ url('/histori') }}">
                                Histori
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <!-- Konten Halaman -->
        <main class="container py-4">
            @yield('content')
        </main>
    </div>

    {{-- Tambahan JS dari halaman --}}
    @yield('scripts')
</body>
</html>

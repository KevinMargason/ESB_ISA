<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'ESB' }}</title>
    <style>
        {!! file_get_contents(resource_path('css/site.css')) !!}
    </style>
</head>

<body>
    @auth
        <div class="topbar">
            <div class="container stack" style="justify-content: space-between;">
                <div class="stack">
                    <span class="brand">ESB</span>
                    <a class="nav-pill {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                        href="{{ route('dashboard') }}">Dashboard</a>
                    <a class="nav-pill {{ request()->routeIs('items.*') ? 'active' : '' }}"
                        href="{{ route('items.index') }}">Items</a>
                    @if(auth()->user()->role === 'admin')
                        <a class="nav-pill {{ request()->routeIs('integrity.*') ? 'active' : '' }}"
                            href="{{ route('integrity.index') }}">Integrity Check</a>
                        <a class="nav-pill {{ request()->routeIs('audit.*') ? 'active' : '' }}"
                            href="{{ route('audit.index') }}">Audit Trail</a>
                    @endif
                </div>
                <div class="stack">
                    <span class="user-chip">Login sebagai <strong>{{ auth()->user()->name }}</strong>
                        ({{ auth()->user()->role }})</span>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="btn danger">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    @endauth

    <main class="container">
        @if(session('success'))
            <div class="alert success">{{ session('success') }}</div>
        @endif

        @if($errors->any())
            <div class="alert error">
                <strong>Terjadi error:</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>

</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'S2CTS Local' }}</title>
    <style>
        :root {
            --bg: #f5f7fb;
            --card: #ffffff;
            --text: #1d2738;
            --muted: #5b6678;
            --accent: #0052cc;
            --accent-soft: #e8f0ff;
            --danger: #b42318;
            --line: #d7ddea;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg);
            color: var(--text);
        }

        .container {
            width: min(1100px, 92%);
            margin: 24px auto;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 18px;
            box-shadow: 0 6px 24px rgba(17, 24, 39, 0.05);
        }

        .topbar {
            background: #0f172a;
            color: #fff;
            padding: 14px 0;
        }

        .topbar a {
            color: #fff;
            text-decoration: none;
            margin-right: 14px;
            font-size: 14px;
        }

        .row {
            display: grid;
            gap: 16px;
            grid-template-columns: repeat(12, 1fr);
        }

        .col-4 { grid-column: span 4; }
        .col-6 { grid-column: span 6; }
        .col-8 { grid-column: span 8; }
        .col-12 { grid-column: span 12; }

        @media (max-width: 900px) {
            .col-4, .col-6, .col-8, .col-12 { grid-column: span 12; }
        }

        h1, h2, h3 { margin-top: 0; }

        .muted { color: var(--muted); }

        .btn {
            display: inline-block;
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            background: var(--accent);
            color: #fff;
        }

        .btn.secondary {
            background: var(--accent-soft);
            color: var(--accent);
        }

        .btn.danger {
            background: #ffe9e7;
            color: var(--danger);
        }

        .form-group { margin-bottom: 12px; }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        input, select, textarea {
            width: 100%;
            border: 1px solid var(--line);
            border-radius: 10px;
            padding: 10px;
            font-size: 14px;
            background: #fff;
            color: var(--text);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            text-align: left;
            border-bottom: 1px solid var(--line);
            padding: 10px 8px;
            font-size: 14px;
            vertical-align: top;
        }

        .badge {
            display: inline-block;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 12px;
            background: #eef2ff;
            color: #2f3f7c;
            font-weight: 600;
        }

        .alert {
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .alert.success {
            background: #e9f9ef;
            color: #146c2e;
            border: 1px solid #bde7cb;
        }

        .alert.error {
            background: #fff1f0;
            color: #912018;
            border: 1px solid #f6c7c3;
        }

        .stack { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
    </style>
</head>
<body>
@auth
    <div class="topbar">
        <div class="container stack" style="justify-content: space-between;">
            <div class="stack">
                <strong>S2CTS Localhost</strong>
                <a href="{{ route('dashboard') }}">Dashboard</a>
                <a href="{{ route('items.index') }}">Items</a>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('integrity.index') }}">Integrity Check</a>
                    <a href="{{ route('audit.index') }}">Audit Trail</a>
                @endif
            </div>
            <div class="stack">
                <span>Login sebagai <strong>{{ auth()->user()->name }}</strong> ({{ auth()->user()->role }})</span>
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

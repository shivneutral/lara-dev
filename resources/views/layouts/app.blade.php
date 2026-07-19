<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard')</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; }
        nav { background: white; padding: 16px 40px; box-shadow: 0 1px 4px rgba(0,0,0,0.06); display: flex; align-items: center; justify-content: space-between; }
        nav .brand { font-weight: 700; color: #1e293b; }
        nav a { color: #2563eb; text-decoration: none; margin-left: 20px; font-size: 14px; }
        main { max-width: 900px; margin: 32px auto; padding: 0 20px; }
        .card { background: white; padding: 28px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        h1 { margin-top: 0; font-size: 22px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 10px 8px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { color: #64748b; font-weight: 600; }
        .btn { display: inline-block; padding: 8px 16px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; text-decoration: none; }
        .btn-secondary { background: #64748b; }
        .btn-danger { background: #dc2626; }
        .btn-small { padding: 5px 10px; font-size: 13px; }
        .actions form, .actions a { display: inline-block; margin-right: 6px; }
        .status { background: #dcfce7; color: #166534; padding: 10px 14px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .error { color: #dc2626; margin-bottom: 12px; font-size: 14px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; }
        input { width: 100%; padding: 10px 12px; margin-bottom: 14px; border: 1px solid #d0d7de; border-radius: 8px; box-sizing: border-box; font-size: 14px; }
        .header-row { display: flex; align-items: center; justify-content: space-between; }
        .empty { color: #64748b; font-size: 14px; padding: 20px 0; }
        .pagination { display: flex; align-items: center; justify-content: center; gap: 16px; margin-top: 20px; }
        .pagination-info { color: #64748b; font-size: 14px; }
        .btn-disabled { background: #cbd5e1; cursor: default; pointer-events: none; }
    </style>
</head>
<body>
    <nav>
        <span class="brand">Lara Dev</span>
        <div>
            <a href="{{ route('dashboard') }}">Dashboard</a>
            <a href="{{ route('customers.index') }}">Customers</a>
            <a href="{{ url('/logout') }}">Logout</a>
        </div>
    </nav>
    <main>
        @yield('content')
    </main>
</body>
</html>

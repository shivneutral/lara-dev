<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f7fb; margin: 0; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { background: white; padding: 32px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); width: 100%; max-width: 400px; }
        h1 { margin-top: 0; font-size: 24px; }
        label { display: block; margin-bottom: 6px; font-weight: 600; }
        input { width: 100%; padding: 10px 12px; margin-bottom: 14px; border: 1px solid #d0d7de; border-radius: 8px; box-sizing: border-box; }
        button { width: 100%; padding: 10px 12px; background: #2563eb; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 15px; }
        .error { color: #dc2626; margin-bottom: 12px; font-size: 14px; }
        .hint { margin-top: 12px; font-size: 13px; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Sign in</h1>
        @if ($errors->any())
            <div class="error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>

            <label for="password">Password</label>
            <input id="password" name="password" type="password" required>

            <button type="submit">Login</button>
        </form>

        <div class="hint">Demo credentials: admin@example.com / password123</div>
    </div>
</body>
</html>

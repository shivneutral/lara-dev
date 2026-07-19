<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f8fafc; margin: 0; padding: 40px; }
        .card { max-width: 480px; margin: 0 auto; background: white; padding: 28px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        h1 { margin-top: 0; }
        a { display: inline-block; margin-top: 16px; color: #2563eb; text-decoration: none; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Welcome, {{ $user['name'] }}!</h1>
        <p>You have successfully logged in.</p>
        <p>Email: {{ $user['email'] }}</p>
        <a href="{{ url('/logout') }}">Logout</a>
    </div>
</body>
</html>

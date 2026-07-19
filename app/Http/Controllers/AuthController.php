<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($validated['email'] === 'admin@example.com' && $validated['password'] === 'password123') {
            session(['user' => [
                'name' => 'Demo Admin',
                'email' => $validated['email'],
            ]]);

            return redirect()->intended('/dashboard');
        }

        return back()
            ->withErrors(['email' => 'Invalid email or password.'])
            ->onlyInput('email');
    }

    public function dashboard()
    {
        return view('auth.dashboard', [
            'user' => session('user'),
        ]);
    }

    public function logout()
    {
        session()->forget('user');

        return redirect('/login');
    }
}

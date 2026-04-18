<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * AuthController — User authentication (login, register, logout).
 */
class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        // Manually check credentials since we use password_hash column
        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password_hash)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            // Update last login
            $user->update(['last_login_at' => now()]);

            return redirect()->intended(route('home'))
                ->with('success', 'Welcome back!');
        }

        return back()->withErrors([
            'email' => 'Invalid credentials.',
        ])->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:100',
            'email'        => 'required|email|unique:users,email',
            'password'     => ['required', 'confirmed', Password::min(8)],
        ]);

        $hashedPassword = Hash::make($validated['password']);

        $user = User::create([
            'name'          => $validated['display_name'],
            'display_name'  => $validated['display_name'],
            'email'         => $validated['email'],
            'password'      => $hashedPassword,
            'password_hash' => $hashedPassword,
        ]);

        Auth::login($user);

        return redirect()->route('home')
            ->with('success', 'Welcome to Where Is My Flight!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

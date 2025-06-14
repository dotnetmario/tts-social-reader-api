<?php

namespace App\Http\Controllers;


use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * LOGIN
     */
    public function login()
    {
        return view('auth.login');
    }

    public function performLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember_me'))) {
            $request->session()->regenerate();

            // return redirect()->intended('/dashboard');
            return redirect()->route('dashboard');
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Invalid credentials.',
        ])->onlyInput('email');
    }


    /**
     * REGISTER
     */
    public function register()
    {
        return view('auth.register');
    }

    public function performRegister(Request $request)
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'regex:/^\+\d{1,3}\d{9,10}$/'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $user = User::create([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        // return redirect()->intended('/dashboard');
        return redirect()->route('dashboard');
    }
}

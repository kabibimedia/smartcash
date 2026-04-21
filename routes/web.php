<?php

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/login'));
Route::get('/login', fn () => view('auth.login'))->name('login');

Route::post('/login', function (Request $request) {
    $login = $request->input('email');
    $password = $request->input('password');

    $admin = User::where('email', 'admin@smartcash.com')->first();

    if ($admin && Hash::check($password, $admin->password) && (strtolower($login) === 'admin' || $login === 'admin@smartcash.com')) {
        session(['user' => $admin->name, 'user_id' => $admin->id]);

        return redirect('/dashboard');
    }

    $user = User::where('email', $login)
        ->orWhere('name', $login)
        ->orWhere('phone', $login)
        ->first();

    if ($user && Hash::check($password, $user->password)) {
        session(['user' => $user->name, 'user_id' => $user->id]);

        return redirect('/dashboard');
    }

    return back()->with('error', 'Invalid credentials');
})->name('login.post');

Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'phone' => 'nullable|string|max:20',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'phone' => $validated['phone'] ?? null,
        'password' => Hash::make($validated['password']),
    ]);

    $user->notify(new WelcomeNotification($user));

    session(['user' => $user->name, 'user_id' => $user->id]);

    return redirect('/dashboard');
})->name('register');

Route::get('/logout', function () {
    session()->flush();

    return redirect('/login');
})->name('logout');

Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
})->name('password.request');

Route::post('/forgot-password', function (Request $request) {
    $email = $request->input('email');
    $user = User::where('email', $email)->first();

    if ($user) {
        return redirect('/login')->with('success', 'Password reset link sent to your email (demo: contact admin to reset)');
    }

    return back()->with('error', 'No account found with that email');
})->name('password.email');

Route::get('/dashboard', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('dashboard');
})->name('dashboard');

Route::get('/obligations', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('obligations.index');
})->name('obligations');

Route::get('/receipts', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('receipts.index');
})->name('receipts');

Route::get('/remittances', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('remittances.index');
})->name('remittances');

Route::get('/reports', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('reports.index');
})->name('reports');

Route::get('/profile', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('profile.index');
})->name('profile');

Route::get('/calendar', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('calendar.index');
})->name('calendar');

Route::get('/users', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('users.index');
})->name('users');

Route::get('/test-email', function () {
    try {
        Mail::raw('Test email from browser - '.date('H:i:s'), function ($m) {
            $m->to('maameaba712@gmail.com')->subject('Browser Test');
        });

        return response('Email sent! Check inbox.');
    } catch (Exception $e) {
        return response('Error: '.$e->getMessage(), 500);
    }
});

Route::get('/audits', function () {
    if (! session()->has('user')) {
        return redirect('/login');
    }

    return view('audits.index');
})->name('audits');

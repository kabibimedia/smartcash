<?php

use App\Http\Middleware\RestoreSessionMiddleware;
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

        return redirect('/dashboard')->withCookie(cookie('smartcash_uid', $admin->id, 120));
    }

    $user = User::where('email', $login)
        ->orWhere('name', $login)
        ->orWhere('phone', $login)
        ->first();

    if ($user && Hash::check($password, $user->password)) {
        session(['user' => $user->name, 'user_id' => $user->id]);

        return redirect('/dashboard')->withCookie(cookie('smartcash_uid', $user->id, 120));
    }

    return back()->with('error', 'Invalid credentials')->withInput();
})->name('login.post');

Route::post('/register', function (Request $request) {
    $validated = $request->validate([
        'surname' => 'required|string|max:255',
        'first_name' => 'required|string|max:255',
        'other_names' => 'nullable|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'date_of_birth' => 'nullable|date|before:today',
        'phone' => 'nullable|string|max:20',
        'password' => 'required|string|min:8|confirmed',
    ]);

    $user = User::create([
        'surname' => $validated['surname'],
        'first_name' => $validated['first_name'],
        'other_names' => $validated['other_names'] ?? null,
        'email' => $validated['email'],
        'date_of_birth' => $validated['date_of_birth'] ?? null,
        'phone' => $validated['phone'] ?? null,
        'password' => Hash::make($validated['password']),
    ]);

    $user->notify(new WelcomeNotification($user));

    session(['user' => $user->first_name . ' ' . $user->surname, 'user_id' => $user->id]);

    return redirect('/dashboard')->withCookie(cookie('smartcash_uid', $user->id, 120));
})->name('register');

Route::get('/logout', function () {
    session()->flush();

    return redirect('/login')->withCookie(cookie('smartcash_uid', null, -1));
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

Route::middleware([RestoreSessionMiddleware::class])->group(function () {
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');
    Route::get('/obligations', fn () => view('obligations.index'))->name('obligations');
    Route::get('/receipts', fn () => view('receipts.index'))->name('receipts');
    Route::get('/remittances', fn () => view('remittances.index'))->name('remittances');
    Route::get('/reports', fn () => view('reports.index'))->name('reports');
    Route::get('/profile', fn () => view('profile.index'))->name('profile');
    Route::get('/calendar', fn () => view('calendar.index'))->name('calendar');
    Route::get('/users', fn () => view('users.index'))->name('users');
    Route::get('/audits', fn () => view('audits.index'))->name('audits');
});
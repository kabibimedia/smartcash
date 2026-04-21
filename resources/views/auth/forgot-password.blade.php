<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - SmartCash</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900">SmartCash</h1>
                <p class="text-gray-500">Reset Password</p>
            </div>
            
            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @else
            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="mb-6">
                    <label class="block text-gray-700 text-sm font-medium mb-2">Email Address</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
                </div>
                <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded-lg hover:bg-gray-700">
                    Send Reset Link
                </button>
            </form>
            @endif
            
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-700">Back to Login</a>
            </div>
        </div>
    </div>
</body>
</html>
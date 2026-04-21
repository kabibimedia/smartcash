<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SmartCash')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 text-gray-900">
    <div class="min-h-screen flex">
        <aside class="w-64 bg-gray-800 text-white hidden md:flex flex-col">
            <div class="p-6 border-b border-gray-700">
                <h1 class="text-xl font-bold">SmartCash</h1>
                <p class="text-sm text-gray-400">Revenue Remittance</p>
            </div>
            <nav class="flex-1 p-4 space-y-1">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->routeIs('dashboard') ? 'bg-gray-700 text-white' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('obligations') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->routeIs('obligations*') ? 'bg-gray-700 text-white' : '' }}">
                    Obligations
                </a>
                <a href="{{ route('receipts') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->routeIs('receipts*') ? 'bg-gray-700 text-white' : '' }}">
                    Receipts
                </a>
                <a href="{{ route('remittances') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->routeIs('remittances*') ? 'bg-gray-700 text-white' : '' }}">
                    Remittances
                </a>
                <a href="{{ route('reports') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->routeIs('reports*') ? 'bg-gray-700 text-white' : '' }}">
                    Reports
                </a>
                <a href="{{ route('calendar') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->routeIs('calendar*') ? 'bg-gray-700 text-white' : '' }}">
                    Calendar
                </a>
                @if(session('user') === 'Admin')
                <a href="{{ route('audits') }}" class="flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white {{ request()->routeIs('audits*') ? 'bg-gray-700 text-white' : '' }}">
                    Audit Trail
                </a>
                @endif
            </nav>
            <div class="p-4 border-t border-gray-700">
                <label class="text-xs text-gray-400 block mb-2">Currency</label>
                <select id="currency-select" onchange="changeCurrency(this.value)" class="w-full px-2 py-2 bg-gray-700 text-white border border-gray-600 rounded text-sm">
                    <option value="GHS">GHS - Ghana Cedis</option>
                    <option value="USD">USD - US Dollar</option>
                    <option value="EUR">EUR - Euro</option>
                    <option value="GBP">GBP - British Pound</option>
                    <option value="NGN">NGN - Naira</option>
                </select>
            </div>
        </aside>
        <main class="flex-1">
            <header class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold">@yield('header', 'Dashboard')</h2>
                    <div class="flex items-center gap-4">
                        @if(session('user') === 'Admin')
                        <a href="{{ route('users') }}" class="text-sm text-gray-700 hover:text-gray-900">Users</a>
                        @endif
                        <span class="text-sm text-gray-500">{{ session('user') }}</span>
                        <a href="{{ route('profile') }}" class="text-sm text-gray-700 hover:text-gray-900">Profile</a>
                        <a href="{{ route('logout') }}" class="text-sm text-red-600 hover:text-red-800">Logout</a>
                    </div>
                </div>
            </header>
            <div class="p-6">
                @yield('content')
            </div>
        </main>
    </div>
    <script>
        const currencies = {
            'GHS': { code: 'GHS', symbol: '₵', locale: 'en-GH' },
            'USD': { code: 'USD', symbol: '$', locale: 'en-US' },
            'EUR': { code: 'EUR', symbol: '€', locale: 'de-DE' },
            'GBP': { code: 'GBP', symbol: '£', locale: 'en-GB' },
            'NGN': { code: 'NGN', symbol: '₦', locale: 'en-NG' }
        };

        function getCurrency() {
            return localStorage.getItem('smartcash_currency') || 'GHS';
        }

        function changeCurrency(code) {
            localStorage.setItem('smartcash_currency', code);
            document.getElementById('currency-select').value = code;
            window.location.reload();
        }

        function formatCurrency(amount) {
            const currency = getCurrency();
            const config = currencies[currency] || currencies['GHS'];
            return new Intl.NumberFormat(config.locale, { 
                style: 'currency', 
                currency: config.code 
            }).format(amount || 0);
        }

        document.getElementById('currency-select').value = getCurrency();
    </script>
</body>
</html>
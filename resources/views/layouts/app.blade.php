<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>@yield('title', 'SmartCash')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#1f2937">
    <script>
        var smartcashUserId = {{ session('user_id', 0) }};
        if (!smartcashUserId && document.cookie) {
            var cookies = document.cookie.split(';');
            for (var i = 0; i < cookies.length; i++) {
                var c = cookies[i].trim();
                if (c.indexOf('smartcash_uid=') === 0) {
                    var val = c.substring('smartcash_uid='.length);
                    if (val && !isNaN(val)) {
                        smartcashUserId = parseInt(val, 10);
                    }
                    break;
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 text-gray-900">
    <!-- Mobile Header -->
    <header id="mobile-header" class="md:hidden bg-gray-800 text-white px-4 py-3 flex items-center justify-between sticky top-0 z-40">
        <button onclick="toggleSidebar()" class="text-white p-2 -ml-2">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <h1 class="text-lg font-bold">SmartCash</h1>
        <div class="w-10"></div>
    </header>

    <div class="flex min-h-screen">
        <!-- Sidebar Overlay (mobile only) -->
        <div id="sidebar-overlay" onclick="toggleSidebar()" class="fixed inset-0 bg-black bg-opacity-50 z-30 hidden"></div>

        <!-- Sidebar -->
        <aside id="sidebar" class="fixed md:relative w-64 bg-gray-800 text-white h-screen md:h-auto flex flex-col z-40 transition-transform duration-200 ease-out -translate-x-full md:translate-x-0">
            <div class="p-4 border-b border-gray-700 flex items-center justify-between md:justify-start">
                <div>
                    <h1 class="text-xl font-bold">SmartCash</h1>
                    <p class="text-sm text-gray-400">Revenue Remittance</p>
                </div>
                <button onclick="toggleSidebar()" class="md:hidden text-gray-400 hover:text-white p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
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

        <!-- Main Content -->
        <main class="flex-1 flex flex-col min-w-0">
            <header class="bg-white border-b border-gray-200 px-3 sm:px-4 md:px-6 py-3 md:py-4 md:mt-0 mt-0">
                <div class="flex items-center justify-between">
                    <h2 class="text-base md:text-lg font-semibold truncate">@yield('header', 'Dashboard')</h2>
                    <div class="flex items-center gap-2 sm:gap-4 text-sm">
                        @if(session('message'))
                        <span class="text-green-600 font-medium text-xs sm:text-sm">{{ session('message') }}</span>
                        @php(session()->forget('message'))
                        @endif
                        @if(session('user') === 'Admin')
                        <a href="{{ route('users') }}" class="text-gray-700 hover:text-gray-900 hidden sm:inline">Users</a>
                        @endif
                        <span class="text-gray-500 hidden sm:inline">{{ session('user') }}</span>
                        <a href="{{ route('profile') }}" class="text-gray-700 hover:text-gray-900">Profile</a>
                        <a href="{{ route('logout') }}" class="text-red-600 hover:text-red-800">Logout</a>
                    </div>
                </div>
            </header>
            <div class="p-2 sm:p-4 md:p-6 flex-1 overflow-x-auto">
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            
            if (sidebar.classList.contains('translate-x-0')) {
                sidebar.classList.remove('translate-x-0');
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            } else {
                sidebar.classList.remove('-translate-x-full');
                sidebar.classList.add('translate-x-0');
                overlay.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        }

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

        function formatCurrency(amount, currencyCode = null) {
            const currency = currencyCode || getCurrency();
            const config = currencies[currency] || currencies['GHS'];
            return new Intl.NumberFormat(config.locale, { 
                style: 'currency', 
                currency: config.code 
            }).format(amount || 0);
        }

        if (document.getElementById('currency-select')) {
            document.getElementById('currency-select').value = getCurrency();
        }
    </script>
</body>
</html>
@extends('layouts.app')

@section('title', 'Dashboard - SmartCash')
@section('header', 'Dashboard')

@section('content')
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <p class="text-xs sm:text-sm text-gray-500 mb-1">Total Expected</p>
        <p class="text-lg sm:text-2xl font-bold text-gray-800" id="total-expected">₵0.00</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <p class="text-xs sm:text-sm text-gray-500 mb-1">Total Received</p>
        <p class="text-lg sm:text-2xl font-bold text-gray-800" id="total-received">₵0.00</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <p class="text-xs sm:text-sm text-gray-500 mb-1">Total Remitted</p>
        <p class="text-lg sm:text-2xl font-bold text-gray-800" id="total-remitted">₵0.00</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <p class="text-xs sm:text-sm text-gray-500 mb-1">Outstanding</p>
        <p class="text-lg sm:text-2xl font-bold text-gray-800" id="total-outstanding">₵0.00</p>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <p class="text-xs sm:text-sm text-gray-500 mb-1">Pending</p>
        <p class="text-xl sm:text-2xl font-bold text-gray-800" id="count-pending">0</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <p class="text-xs sm:text-sm text-gray-500 mb-1">Overdue</p>
        <p class="text-xl sm:text-2xl font-bold text-red-600" id="count-overdue">0</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <p class="text-xs sm:text-sm text-gray-500 mb-1">Received</p>
        <p class="text-xl sm:text-2xl font-bold text-green-600" id="count-received">0</p>
    </div>
    <div class="bg-white rounded-xl p-4 border border-gray-200">
        <p class="text-xs sm:text-sm text-gray-500 mb-1">Remitted</p>
        <p class="text-xl sm:text-2xl font-bold text-blue-600" id="count-remitted">0</p>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-4 sm:p-6">
    <h3 class="text-lg font-semibold mb-4">Recent Obligations</h3>
    <div class="overflow-x-auto -mx-4 sm:mx-0">
        <table class="w-full min-w-[600px]">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Title</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 hidden sm:table-cell">Expected</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500 hidden sm:table-cell">Received</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Due Date</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody id="recent-obligations">
                <tr>
                    <td colspan="5" class="py-4 px-4 text-center text-gray-500">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadDashboard() {
    console.log('loadDashboard called, smartcashUserId:', smartcashUserId);
    try {
        const response = await fetch('/api/v1/reports/dashboard', {
            credentials: 'include',
            headers: { 
                'Accept': 'application/json', 
                'X-User-Id': smartcashUserId.toString() 
            }
        });
        console.log('Dashboard response status:', response.status);
        const result = await response.json();
        console.log('Dashboard result:', result);
        
        if (result.success) {
            const data = result.data;
            document.getElementById('total-expected').textContent = formatCurrency(data.totals.expected);
            document.getElementById('total-received').textContent = formatCurrency(data.totals.received);
            document.getElementById('total-remitted').textContent = formatCurrency(data.totals.remitted);
            document.getElementById('total-outstanding').textContent = formatCurrency(data.totals.outstanding);
            document.getElementById('count-pending').textContent = data.counts.pending;
            document.getElementById('count-overdue').textContent = data.counts.overdue;
            document.getElementById('count-received').textContent = data.counts.received;
            document.getElementById('count-remitted').textContent = data.counts.remitted;
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

async function loadRecentObligations() {
    try {
        const response = await fetch('/api/v1/obligations', {
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'X-User-Id': smartcashUserId.toString() }
        });
        const result = await response.json();
        
        const tbody = document.getElementById('recent-obligations');
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.slice(0, 5).map(obs => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4">
                        <span class="font-medium">${obs.title}</span>
                        <div class="sm:hidden text-xs text-gray-500 mt-1">
                            Exp: ${formatCurrency(obs.amount_expected)} | Rec: ${formatCurrency(obs.amount_received)}
                        </div>
                    </td>
                    <td class="py-3 px-4 hidden sm:table-cell">${formatCurrency(obs.amount_expected)}</td>
                    <td class="py-3 px-4 hidden sm:table-cell">${formatCurrency(obs.amount_received)}</td>
                    <td class="py-3 px-4 text-sm">${obs.formatted_due_date}</td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(obs.status)}">
                            ${obs.status}
                        </span>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="py-4 px-4 text-center text-gray-500">No obligations yet</td></tr>';
        }
    } catch (error) {
        console.error('Error loading obligations:', error);
    }
}

function getStatusClass(status) {
    const classes = {
        'pending': 'bg-gray-200 text-gray-700',
        'partially_paid': 'bg-yellow-200 text-yellow-700',
        'received': 'bg-green-200 text-green-700',
        'remitted': 'bg-gray-400 text-gray-800',
        'overdue': 'bg-red-200 text-red-700'
    };
    return classes[status] || 'bg-gray-200 text-gray-700';
}

loadDashboard();
loadRecentObligations();
</script>
@endsection
@extends('layouts.app')

@section('title', 'Reports - SmartCash')
@section('header', 'Reports')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-6 md:mb-8">
    <div class="bg-white rounded-xl border border-gray-200 p-4 md:p-6">
        <h3 class="text-base md:text-lg font-semibold mb-4">Monthly Summary</h3>
        <div class="flex flex-wrap gap-2 mb-4">
            <input type="number" id="month" placeholder="Month" min="1" max="12" class="w-16 sm:w-20 px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-sm" value="{{ date('m') }}">
            <input type="number" id="year" placeholder="Year" class="w-20 sm:w-24 px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-sm" value="{{ date('Y') }}">
            <button onclick="loadMonthly()" class="bg-blue-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">Go</button>
        </div>
        <div id="monthly-summary" class="space-y-2 sm:space-y-3">
            <p class="text-gray-500 text-sm">Select month and year to view summary</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 md:p-6">
        <h3 class="text-base md:text-lg font-semibold mb-4">Outstanding</h3>
        <div id="outstanding-list" class="space-y-2 max-h-48 overflow-y-auto">
            <p class="text-gray-500 text-sm">Loading...</p>
        </div>
    </div>
    <div class="bg-white rounded-xl border border-gray-200 p-4 md:p-6">
        <h3 class="text-base md:text-lg font-semibold mb-4">Overdue</h3>
        <div id="overdue-list" class="space-y-2 max-h-48 overflow-y-auto">
            <p class="text-gray-500 text-sm">Loading...</p>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-4 md:p-6">
    <h3 class="text-lg font-semibold mb-4">Full Statement</h3>
    <div class="flex flex-wrap gap-2 mb-4">
        <input type="date" id="from-date" class="px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-sm" value="{{ date('Y-01-01') }}">
        <input type="date" id="to-date" class="px-2 sm:px-3 py-2 border border-gray-300 rounded-lg text-sm" value="{{ date('Y-m-d') }}">
        <button onclick="loadStatement()" class="bg-gray-800 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-gray-700 text-sm">View</button>
        <button onclick="exportExcel()" class="bg-gray-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-gray-500 text-sm">Excel</button>
        <button onclick="exportPdf()" class="bg-gray-600 text-white px-3 sm:px-4 py-2 rounded-lg hover:bg-gray-500 text-sm">PDF</button>
    </div>
    <div class="overflow-x-auto -mx-4 px-4 md:mx-0 md:px-0">
        <table class="w-full min-w-[700px]">
            <thead class="bg-gray-50">
                <tr>
                    <th class="text-left py-3 px-3 text-sm font-medium text-gray-500">Obligation</th>
                    <th class="text-left py-3 px-3 text-sm font-medium text-gray-500">Expected</th>
                    <th class="text-left py-3 px-3 text-sm font-medium text-gray-500">Received</th>
                    <th class="text-left py-3 px-3 text-sm font-medium text-gray-500">Paid</th>
                    <th class="text-left py-3 px-3 text-sm font-medium text-gray-500">Status</th>
                </tr>
            </thead>
            <tbody id="statement-table">
                <tr>
                    <td colspan="5" class="py-4 px-3 text-center text-gray-500">Select date range and click View</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
async function loadMonthly() {
    const month = document.getElementById('month').value;
    const year = document.getElementById('year').value;
    
    try {
        const response = await fetch(`/api/v1/reports/monthly?month=${month}&year=${year}`, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        const container = document.getElementById('monthly-summary');
        
        if (result.success) {
            const data = result.data;
            container.innerHTML = `
                <div class="flex justify-between py-1">
                    <span class="text-gray-500 text-sm">Total Expected</span>
                    <span class="font-medium text-sm">${formatCurrency(data.total_expected)}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-500 text-sm">Total Received</span>
                    <span class="font-medium text-green-600 text-sm">${formatCurrency(data.total_received)}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-500 text-sm">Total Paid</span>
                    <span class="font-medium text-blue-600 text-sm">${formatCurrency(data.total_paid)}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-500 text-sm">Outstanding</span>
                    <span class="font-medium text-orange-600 text-sm">${formatCurrency(data.outstanding)}</span>
                </div>
                <div class="flex justify-between py-1">
                    <span class="text-gray-500 text-sm">Late Payments</span>
                    <span class="font-medium text-red-600 text-sm">${data.late_payments}</span>
                </div>
            `;
        } else {
            container.innerHTML = '<p class="text-gray-500 text-sm">No data for this period</p>';
        }
    } catch (error) {
        console.error('Error loading monthly:', error);
    }
}

async function loadOutstanding() {
    try {
        const response = await fetch('/api/v1/reports/outstanding', {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        const container = document.getElementById('outstanding-list');
        
        if (result.success && result.data.length > 0) {
            container.innerHTML = result.data.map(obs => `
                <div class="flex justify-between py-2 border-b border-gray-100 text-sm">
                    <div>
                        <p class="font-medium truncate">${obs.title}</p>
                        <p class="text-xs text-gray-500">Due: ${obs.formatted_due_date}</p>
                    </div>
                    <div class="text-right ml-2">
                        <p class="font-medium text-orange-600">${formatCurrency(obs.outstanding)}</p>
                        <p class="text-xs ${obs.status === 'overdue' ? 'text-red-600' : 'text-gray-500'}">${obs.status}</p>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-gray-500 text-sm">No outstanding payments</p>';
        }
    } catch (error) {
        console.error('Error loading outstanding:', error);
    }
}

async function loadOverdue() {
    try {
        const response = await fetch('/api/v1/reports/overdue', {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        const container = document.getElementById('overdue-list');
        
        if (result.success && result.data.length > 0) {
            container.innerHTML = result.data.map(obs => `
                <div class="flex justify-between py-2 border-b border-gray-100 text-sm">
                    <div>
                        <p class="font-medium truncate">${obs.title}</p>
                        <p class="text-xs text-gray-500">Due: ${obs.formatted_due_date}</p>
                    </div>
                    <div class="text-right ml-2">
                        <p class="font-medium text-red-600">${formatCurrency(obs.outstanding)}</p>
                        <p class="text-xs text-red-600">${obs.days_overdue} days overdue</p>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-gray-500 text-sm">No overdue payments</p>';
        }
    } catch (error) {
        console.error('Error loading overdue:', error);
    }
}

async function loadStatement() {
    const from = document.getElementById('from-date').value;
    const to = document.getElementById('to-date').value;
    
    try {
        const response = await fetch(`/api/v1/reports/statement?from=${from}&to=${to}`, {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        const tbody = document.getElementById('statement-table');
        
        console.log('Statement result:', result);
        
        if (result.success && result.data.obligations && result.data.obligations.length > 0) {
            tbody.innerHTML = result.data.obligations.map(obs => {
                const receivedTotal = obs.received.reduce((sum, r) => sum + parseFloat(r.amount || 0), 0);
                const remittedTotal = obs.remitted.reduce((sum, r) => sum + parseFloat(r.amount || 0), 0);
                
                return `
                    <tr class="border-b border-gray-100 hover:bg-gray-50">
                        <td class="py-3 px-3">
                            <p class="font-medium text-sm">${obs.obligation.title}</p>
                            <p class="text-xs text-gray-500">${obs.obligation.formatted_due_date || obs.obligation.due_date}</p>
                        </td>
                        <td class="py-3 px-3 text-sm">${formatCurrency(obs.obligation.amount_expected)}</td>
                        <td class="py-3 px-3 text-sm text-green-600">${formatCurrency(obs.amount_received || receivedTotal)}</td>
                        <td class="py-3 px-3 text-sm text-blue-600">${formatCurrency(obs.amount_remitted || remittedTotal)}</td>
                        <td class="py-3 px-3">
                            <span class="px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(obs.status)}">${obs.status}</span>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="py-4 px-3 text-center text-gray-500">No records found</td></tr>';
        }
    } catch (error) {
        console.error('Error loading statement:', error);
        tbody.innerHTML = '<tr><td colspan="5" class="py-4 px-3 text-center text-red-500">Error loading data</td></tr>';
    }
}

function getStatusClass(status) {
    const classes = {
        'pending': 'bg-gray-100 text-gray-600',
        'partially_paid': 'bg-yellow-100 text-yellow-600',
        'received': 'bg-green-100 text-green-600',
        'remitted': 'bg-blue-100 text-blue-600',
        'overdue': 'bg-red-100 text-red-600'
    };
    return classes[status] || 'bg-gray-100 text-gray-600';
}

loadMonthly();
loadOutstanding();
loadOverdue();

function exportExcel() {
    const from = document.getElementById('from-date').value;
    const to = document.getElementById('to-date').value;
    window.open(`/api/v1/reports/export/excel?from=${from}&to=${to}`, '_blank');
}

function exportPdf() {
    const from = document.getElementById('from-date').value;
    const to = document.getElementById('to-date').value;
    window.open(`/api/v1/reports/export/pdf?from=${from}&to=${to}`, '_blank');
}
</script>
@endsection
@extends('layouts.app')

@section('title', 'Dashboard - SmartCash')
@section('header', 'Dashboard')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h2 class="text-xl font-semibold">Dashboard</h2>
    <div class="flex gap-2">
        <button onclick="downloadTemplate()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
            Download Template
        </button>
        <button onclick="openImportModal()" class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-700">
            Import Data
        </button>
    </div>
</div>

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
    <h3 class="text-lg font-semibold mb-4">Currency Breakdown</h3>
    <div id="currency-breakdown">
        <p class="text-gray-500 text-sm">Loading...</p>
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
            const defaultCurrency = getCurrency();
            
            // Find default currency data
            const defaultData = data.by_currency?.find(c => c.currency === defaultCurrency);
            if (defaultData) {
                document.getElementById('total-expected').textContent = formatCurrency(defaultData.expected, defaultCurrency);
                document.getElementById('total-received').textContent = formatCurrency(defaultData.received, defaultCurrency);
                document.getElementById('total-remitted').textContent = formatCurrency(defaultData.remitted, defaultCurrency);
                document.getElementById('total-outstanding').textContent = formatCurrency(defaultData.outstanding, defaultCurrency);
            } else {
                document.getElementById('total-expected').textContent = formatCurrency(0);
                document.getElementById('total-received').textContent = formatCurrency(0);
                document.getElementById('total-remitted').textContent = formatCurrency(0);
                document.getElementById('total-outstanding').textContent = formatCurrency(0);
            }
            
            document.getElementById('count-pending').textContent = data.counts.pending;
            document.getElementById('count-overdue').textContent = data.counts.overdue;
            document.getElementById('count-received').textContent = data.counts.received;
            document.getElementById('count-remitted').textContent = data.counts.remitted;
            
            // Show other currencies in separate sections
            const currencyContainer = document.getElementById('currency-breakdown');
            if (currencyContainer && data.by_currency && data.by_currency.length > 0) {
                const otherCurrencies = data.by_currency.filter(c => c.currency !== defaultCurrency);
                
                if (otherCurrencies.length > 0) {
                    let html = '';
                    otherCurrencies.forEach(curr => {
                        html += `
                            <div class="border-t pt-2 mt-2">
                                <p class="text-xs font-semibold text-gray-700 mb-1">${curr.currency}</p>
                                <div class="flex justify-between py-1">
                                    <span class="text-gray-500 text-sm">Expected</span>
                                    <span class="font-medium text-sm">${formatCurrency(curr.expected, curr.currency)}</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-gray-500 text-sm">Received</span>
                                    <span class="font-medium text-green-600 text-sm">${formatCurrency(curr.received, curr.currency)}</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-gray-500 text-sm">Remitted</span>
                                    <span class="font-medium text-blue-600 text-sm">${formatCurrency(curr.remitted, curr.currency)}</span>
                                </div>
                                <div class="flex justify-between py-1">
                                    <span class="text-gray-500 text-sm">Outstanding</span>
                                    <span class="font-medium text-orange-600 text-sm">${formatCurrency(curr.outstanding, curr.currency)}</span>
                                </div>
                            </div>
                        `;
                    });
                    currencyContainer.innerHTML = html;
                } else {
                    currencyContainer.innerHTML = '<p class="text-gray-500 text-sm">No other currencies</p>';
                }
            }
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
                            Exp: ${formatCurrency(obs.amount_expected, obs.currency)} | Rec: ${formatCurrency(obs.amount_received, obs.currency)}
                        </div>
                    </td>
                    <td class="py-3 px-4 hidden sm:table-cell">${formatCurrency(obs.amount_expected, obs.currency)}</td>
                    <td class="py-3 px-4 hidden sm:table-cell">${formatCurrency(obs.amount_received, obs.currency)}</td>
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

function downloadTemplate() {
    const csv = 'type,title,amount,currency,date,frequency,description,repeat_type,repeat_until\n' +
        'obligation,Rent,50000,GHS,2026-05-01,monthly,Monthly rent,\n' +
        'obligation,Salary,100000,USD,2026-05-05,monthly,,\n' +
        'reminder,Doctor Appointment,,GHS,2026-05-10,,Annual checkup,yearly,2026-12-31\n';
    
    const blob = new Blob([csv], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'smartcash-import-template.csv';
    a.click();
    window.URL.revokeObjectURL(url);
}

function openImportModal() {
    document.getElementById('import-modal').classList.remove('hidden');
    document.getElementById('import-modal').classList.add('flex');
}

function closeImportModal() {
    document.getElementById('import-modal').classList.add('hidden');
    document.getElementById('import-modal').classList.remove('flex');
    document.getElementById('import-form').reset();
    document.getElementById('import-results').innerHTML = '';
}

async function importData(e) {
    e.preventDefault();
    const form = document.getElementById('import-form');
    const formData = new FormData(form);
    
    if (!formData.get('file')) {
        alert('Please select a file');
        return;
    }
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Importing...';
    
    try {
        const response = await fetch('/api/v1/import', {
            method: 'POST',
            credentials: 'include',
            headers: { 
                'X-User-Id': smartcashUserId.toString(),
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('import-results').innerHTML = `
                <div class="text-green-600 text-sm">
                    Import successful! Imported ${result.data.imported} records.
                    ${result.data.errors?.length ? '<br>Errors: ' + result.data.errors.join(', ') : ''}
                </div>
            `;
            setTimeout(() => {
                closeImportModal();
                loadDashboard();
                loadRecentObligations();
            }, 2000);
        } else {
            document.getElementById('import-results').innerHTML = `
                <div class="text-red-600 text-sm">${result.message || 'Import failed'}</div>
            `;
        }
    } catch (error) {
        document.getElementById('import-results').innerHTML = `
            <div class="text-red-600 text-sm">Error importing data</div>
        `;
    }
    
    submitBtn.disabled = false;
    submitBtn.textContent = 'Import';
}

loadDashboard();
loadRecentObligations();
</script>

<div id="import-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4 sm:p-6">
    <div class="bg-white rounded-xl p-4 sm:p-6 w-full max-w-lg mx-auto shadow-xl">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Import Data</h3>
            <button onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="import-form" onsubmit="importData(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                <input type="file" name="file" accept=".csv" required class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <p class="text-xs text-gray-500 mt-1">Download the template above for the correct format</p>
            </div>
            <div id="import-results" class="mb-4"></div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeImportModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Import</button>
            </div>
        </form>
    </div>
</div>
@endsection
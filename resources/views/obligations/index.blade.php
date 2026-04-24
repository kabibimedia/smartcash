@extends('layouts.app')

@section('title', 'Obligations - SmartCash')
@section('header', 'Obligations')

@section('content')
<div class="mb-6 flex justify-end">
    <button onclick="openModal()" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add Obligation
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Title</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Expected</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Received</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Due Date</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Frequency</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Email</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Created</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Status</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody id="obligations-table">
            <tr>
                <td colspan="9" class="py-4 px-4 text-center text-gray-500">Loading...</td>
            </tr>
        </tbody>
    </table>
</div>

<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Add Obligation</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="obligation-form" onsubmit="saveObligation(event)">
            <input type="hidden" name="id" id="obligation-id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Expected</label>
                    <input type="number" name="amount_expected" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input type="date" name="due_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                    <select name="frequency" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="one-time">One-time</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notification Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="optional">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
    document.getElementById('obligation-id').value = '';
    document.getElementById('obligation-form').reset();
}

function editObligation(id, title, amount, dueDate, frequency, notes, email = '') {
    document.getElementById('obligation-id').value = id;
    document.querySelector('input[name="title"]').value = title;
    document.querySelector('input[name="amount_expected"]').value = amount;
    document.querySelector('input[name="due_date"]').value = dueDate;
    document.querySelector('select[name="frequency"]').value = frequency;
    document.querySelector('textarea[name="notes"]').value = notes || '';
    document.querySelector('input[name="email"]').value = email || '';
    
    document.querySelector('#modal h3').textContent = 'Edit Obligation';
    openModal();
}

async function loadObligations() {
    try {
        const response = await fetch('/api/v1/obligations', { 
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        const tbody = document.getElementById('obligations-table');
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(obs => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4">${obs.title}</td>
                    <td class="py-3 px-4">${formatCurrency(obs.amount_expected)}</td>
                    <td class="py-3 px-4">${formatCurrency(obs.amount_received)}</td>
                    <td class="py-3 px-4">${obs.formatted_due_date}</td>
                    <td class="py-3 px-4 capitalize">${obs.frequency}</td>
                    <td class="py-3 px-4 text-xs text-gray-500">${obs.email || '-'}</td>
                    <td class="py-3 px-4 text-xs text-gray-500">${obs.created_at_timestamp}</td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 rounded-full text-xs font-medium ${getStatusClass(obs.status)}">${obs.status}</span>
                    </td>
                    <td class="py-3 px-4">
                        <button onclick="confirmEditObligation(${obs.id}, '${obs.title}', ${obs.amount_expected}, '${obs.input_due_date}', '${obs.frequency}', '${obs.notes || ''}', '${obs.email || ''}')" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                        <button onclick="confirmDeleteObligation(${obs.id}, '${obs.title}')" class="text-red-600 hover:text-red-800">Delete</button></button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="py-4 px-4 text-center text-gray-500">No obligations yet</td></tr>';
        }
    } catch (error) {
        console.error('Error loading obligations:', error);
    }
}

async function saveObligation(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());
    
    const id = document.getElementById('obligation-id').value;
    const isEdit = id && id !== '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    try {
        const url = isEdit ? `/api/v1/obligations/${id}` : '/api/v1/obligations';
        const method = isEdit ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            credentials: 'include',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-User-Id': smartcashUserId.toString(),
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });
        
        const text = await response.text();
        console.log('Response status:', response.status);
        console.log('Response body:', text);
        
        const result = JSON.parse(text);
        
        if (result.success) {
            closeModal();
            form.reset();
            document.querySelector('#modal h3').textContent = 'Add Obligation';
            loadObligations();
            alert(isEdit ? 'Obligation updated successfully!' : 'Obligation created successfully!');
        } else {
            alert('Error: ' + (result.message || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error saving obligation:', error);
        alert('Error saving obligation: ' + error.message);
    }
}

async function deleteObligation(id) {
    if (!confirm('Are you sure you want to DELETE this obligation? This cannot be undone.')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    try {
        const response = await fetch(`/api/v1/obligations/${id}`, { 
            method: 'DELETE',
            credentials: 'include',
            headers: { 
                'X-User-Id': smartcashUserId.toString(),
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            }
        });
        const result = await response.json();
        
        if (result.success) {
            loadObligations();
            alert('Obligation deleted successfully!');
        }
    } catch (error) {
        console.error('Error deleting obligation:', error);
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

function confirmEditObligation(id, title, amount, dueDate, frequency, notes, email) {
    if (!confirm('Are you sure you want to edit "' + title + '"?')) return;
    editObligation(id, title, amount, dueDate, frequency, notes, email);
}

function confirmDeleteObligation(id, title) {
    if (!confirm('Are you sure you want to DELETE "' + title + '"? This cannot be undone.')) return;
    deleteObligation(id);
}

loadObligations();
</script>
@endsection
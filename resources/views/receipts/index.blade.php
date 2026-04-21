@extends('layouts.app')

@section('title', 'Receipts - SmartCash')
@section('header', 'Receipts')

@section('content')
<div class="mb-6 flex justify-end">
    <button onclick="openModal()" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Record Receipt
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Obligation</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Amount</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Date Received</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Method</th>
<th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Reference</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Image</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Created</th>
                    <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody id="receipts-table">
            <tr>
                <td colspan="6" class="py-4 px-4 text-center text-gray-500">Loading...</td>
            </tr>
        </tbody>
    </table>
</div>

<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Record Receipt</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="receipt-form" onsubmit="saveReceipt(event)">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Obligation</label>
                    <select name="obligation_id" id="obligation-select" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="">Select an obligation</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Received</label>
                    <input type="number" name="amount_received" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Received</label>
                    <input type="date" name="date_received" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="cheque">Cheque</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                    <input type="text" name="reference" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notification Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500" placeholder="optional">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proof Image</label>
                    <input type="file" name="image" accept="image/*" capture="environment" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    <p class="text-xs text-gray-500 mt-1">Take a photo or upload from device</p>
                    <div id="existing-image" class="mt-2"></div>
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Save</button>
            </div>
        <input type="hidden" name="id" id="receipt-id" value="">
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
    loadObligations();
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
    document.getElementById('receipt-id').value = '';
    document.getElementById('receipt-form').reset();
    document.querySelector('#modal h3').textContent = 'Record Receipt';
    const existingImage = document.getElementById('existing-image');
    if (existingImage) existingImage.innerHTML = '';
}

function editReceipt(id, obligationId, amount, dateReceived, method, reference, notes, email = '', imageUrl = '') {
    document.getElementById('receipt-id').value = id;
    loadObligations().then(() => {
        document.querySelector('select[name="obligation_id"]').value = obligationId;
    });
    document.querySelector('input[name="amount_received"]').value = amount;
    document.querySelector('input[name="date_received"]').value = dateReceived;
    document.querySelector('select[name="payment_method"]').value = method;
    document.querySelector('input[name="reference"]').value = reference || '';
    document.querySelector('textarea[name="notes"]').value = notes || '';
    document.querySelector('input[name="email"]').value = email || '';
    
    const existingImage = document.getElementById('existing-image');
    if (imageUrl && existingImage) {
        existingImage.innerHTML = `<a href="${imageUrl}" target="_blank" class="text-blue-600 hover:text-blue-800">View Current Image</a>`;
    }
    
    document.querySelector('#modal h3').textContent = 'Edit Receipt';
    openModal();
}

async function loadObligations() {
    try {
        const response = await fetch('/api/v1/obligations');
        const result = await response.json();
        const select = document.getElementById('obligation-select');
        
        if (result.success) {
            select.innerHTML = '<option value="">Select an obligation</option>' + 
                result.data.map(obs => `<option value="${obs.id}">${obs.title} (${formatCurrency(obs.amount_expected)})</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading obligations:', error);
    }
}

async function loadReceipts() {
    try {
        const response = await fetch('/api/v1/receipts');
        const result = await response.json();
        const tbody = document.getElementById('receipts-table');
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(receipt => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4">${receipt.obligation?.title || 'N/A'}</td>
                    <td class="py-3 px-4 font-medium text-green-600">${formatCurrency(receipt.amount_received)}</td>
                    <td class="py-3 px-4">${receipt.formatted_date_received}</td>
                    <td class="py-3 px-4 capitalize">${receipt.payment_method}</td>
                    <td class="py-3 px-4">${receipt.reference || '-'}</td>
                    <td class="py-3 px-4">
                        ${receipt.image_url ? `<a href="${receipt.image_url}" target="_blank" class="text-blue-600 hover:text-blue-800">View Image</a>` : '-'}
                    </td>
                    <td class="py-3 px-4 text-xs text-gray-500">${receipt.created_at_timestamp}</td>
                    <td class="py-3 px-4">
                        <button onclick="editReceipt(${receipt.id}, ${receipt.obligation_id}, ${receipt.amount_received}, '${receipt.input_date_received}', '${receipt.payment_method}', '${receipt.reference || ''}', '${receipt.notes || ''}', '${receipt.email || ''}', '${receipt.image_url || ''}')" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                        <button onclick="deleteReceipt(${receipt.id})" class="text-red-600 hover:text-red-800">Delete</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="py-4 px-4 text-center text-gray-500">No receipts yet</td></tr>';
        }
    } catch (error) {
        console.error('Error loading receipts:', error);
    }
}

async function saveReceipt(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    const id = document.getElementById('receipt-id').value;
    const isEdit = id && id !== '';
    
    try {
        const url = isEdit ? `/api/v1/receipts/${id}` : '/api/v1/receipts';
        const method = isEdit ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            form.reset();
            document.querySelector('#modal h3').textContent = 'Record Receipt';
            loadReceipts();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving receipt:', error);
        alert('Error saving receipt');
    }
}

async function deleteReceipt(id) {
    if (!confirm('Are you sure you want to delete this receipt?')) return;
    
    try {
        const response = await fetch(`/api/v1/receipts/${id}`, { method: 'DELETE' });
        const result = await response.json();
        
        if (result.success) {
            loadReceipts();
        }
    } catch (error) {
        console.error('Error deleting receipt:', error);
    }
}

loadReceipts();
</script>
@endsection
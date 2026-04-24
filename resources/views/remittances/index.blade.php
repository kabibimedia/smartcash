@extends('layouts.app')

@section('title', 'Remittances - SmartCash')
@section('header', 'Remittances')

@section('content')
<div class="mb-6 flex justify-end">
    <button onclick="openModal()" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Record Payment
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Receipt</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Amount</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Date Paid</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Method</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Reference</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Image</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Created</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody id="remittances-table">
            <tr>
                <td colspan="8" class="py-4 px-4 text-center text-gray-500">Loading...</td>
            </tr>
        </tbody>
    </table>
</div>

<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Record Payment to Boss</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="remittance-form" onsubmit="saveRemittance(event)">
            <input type="hidden" name="id" id="remittance-id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Receipt</label>
                    <select name="receipt_id" id="receipt-select" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select a receipt</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Amount Paid</label>
                    <input type="number" name="amount_paid" step="0.01" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Paid</label>
                    <input type="date" name="date_paid" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                    <select name="payment_method" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="cheque">Cheque</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                    <input type="text" name="reference" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notification Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="optional">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Proof Image</label>
                    <input type="file" name="image" accept="image/*" capture="environment" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <p class="text-xs text-gray-500 mt-1">Take a photo or upload from device</p>
                    <div id="existing-image" class="mt-2"></div>
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
    loadReceipts();
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
    document.getElementById('remittance-id').value = '';
    document.getElementById('remittance-form').reset();
    document.querySelector('#modal h3').textContent = 'Record Payment to Boss';
    const existingImage = document.getElementById('existing-image');
    if (existingImage) existingImage.innerHTML = '';
}

function editRemittance(id, receiptId, amount, date, method, reference, notes, email = '', imageUrl = '') {
    document.getElementById('remittance-id').value = id;
    loadReceipts().then(() => {
        document.querySelector('select[name="receipt_id"]').value = receiptId;
    });
    document.querySelector('input[name="amount_paid"]').value = amount;
    document.querySelector('input[name="date_paid"]').value = date;
    document.querySelector('select[name="payment_method"]').value = method;
    document.querySelector('input[name="reference"]').value = reference || '';
    document.querySelector('textarea[name="notes"]').value = notes || '';
    document.querySelector('input[name="email"]').value = email || '';
    
    const existingImage = document.getElementById('existing-image');
    if (imageUrl && existingImage) {
        existingImage.innerHTML = `<a href="${imageUrl}" target="_blank" class="text-blue-600 hover:text-blue-800">View Current Image</a>`;
    }
    
    document.querySelector('#modal h3').textContent = 'Edit Payment';
    openModal();
}

async function loadReceipts() {
    try {
        const response = await fetch('/api/v1/receipts', {
            credentials: 'include',
            headers: { 'Accept': 'application/json' }
        });
        const result = await response.json();
        const select = document.getElementById('receipt-select');
        
        if (result.success) {
            select.innerHTML = '<option value="">Select a receipt</option>' + 
                result.data.map(rec => `<option value="${rec.id}">${rec.obligation?.title || 'Receipt #' + rec.id} - ${formatCurrency(rec.amount_received)}</option>`).join('');
        }
    } catch (error) {
        console.error('Error loading receipts:', error);
    }
}

async function loadRemittances() {
    try {
        const response = await fetch('/api/v1/remittances', {
            credentials: 'include',
            headers: { 
                'Accept': 'application/json',
                'X-User-Id': smartcashUserId.toString()
            }
        });
        const result = await response.json();
        const tbody = document.getElementById('remittances-table');
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(rem => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4">${rem.receipt?.obligation?.title || 'N/A'}</td>
                    <td class="py-3 px-4 font-medium text-blue-600">${formatCurrency(rem.amount_paid)}</td>
                    <td class="py-3 px-4">${rem.formatted_date_paid}</td>
                    <td class="py-3 px-4 capitalize">${rem.payment_method}</td>
                    <td class="py-3 px-4">${rem.reference || '-'}</td>
                    <td class="py-3 px-4">
                        ${rem.image_url ? `<a href="${rem.image_url}" target="_blank" class="text-blue-600 hover:text-blue-800">View Image</a>` : '-'}
                    </td>
                    <td class="py-3 px-4 text-xs text-gray-500">${rem.created_at_timestamp}</td>
                    <td class="py-3 px-4">
                        <button onclick="confirmEditRemittance(${rem.id}, ${rem.receipt_id}, ${rem.amount_paid}, '${rem.input_date_paid}', '${rem.payment_method}', '${rem.reference || ''}', '${rem.notes || ''}', '${rem.email || ''}', '${rem.image_url || ''}')" class="text-blue-600 hover:text-blue-800 mr-2">Edit</button>
                        <button onclick="confirmDeleteRemittance(${rem.id})" class="text-red-600 hover:text-red-800">Delete</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="8" class="py-4 px-4 text-center text-gray-500">No remittances yet</td></tr>';
        }
    } catch (error) {
        console.error('Error loading remittances:', error);
    }
}

async function saveRemittance(event) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    const id = document.getElementById('remittance-id').value;
    const isEdit = id && id !== '';
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    try {
        const url = isEdit ? `/api/v1/remittances/${id}` : '/api/v1/remittances';
        const method = isEdit ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            credentials: 'include',
            headers: { 
                'X-User-Id': smartcashUserId.toString(),
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            form.reset();
            document.querySelector('#modal h3').textContent = 'Record Payment to Boss';
            loadRemittances();
            alert(isEdit ? 'Payment updated successfully!' : 'Payment recorded successfully!');
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error saving remittance:', error);
        alert('Error saving remittance');
    }
}

async function deleteRemittance(id) {
    if (!confirm('Are you sure you want to DELETE this payment? This cannot be undone.')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    
    try {
        const response = await fetch(`/api/v1/remittances/${id}`, { 
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
            loadRemittances();
            alert('Payment deleted successfully!');
        }
    } catch (error) {
        console.error('Error deleting remittance:', error);
    }
}

function confirmEditRemittance(id, receiptId, amount, datePaid, method, reference, notes, email, imageUrl) {
    if (!confirm('Are you sure you want to edit this payment?')) return;
    editRemittance(id, receiptId, amount, datePaid, method, reference, notes, email, imageUrl);
}

function confirmDeleteRemittance(id) {
    deleteRemittance(id);
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(amount || 0);
}

loadRemittances();
</script>
@endsection
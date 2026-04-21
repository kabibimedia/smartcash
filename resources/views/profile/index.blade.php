@extends('layouts.app')

@section('title', 'Profile - SmartCash')
@section('header', 'My Profile')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Account Information</h3>
            <button onclick="openEditModal()" class="text-blue-600 hover:text-blue-800 text-sm">Edit</button>
        </div>
        <div class="space-y-4">
            <div>
                <label class="block text-sm text-gray-500 mb-1">Name</label>
                <p class="font-medium" id="user-name">{{ session('user') }}</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Email</label>
                <p class="font-medium" id="user-email">-</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Phone</label>
                <p class="font-medium" id="user-phone">-</p>
            </div>
            <div>
                <label class="block text-sm text-gray-500 mb-1">Member Since</label>
                <p class="font-medium" id="user-created">-</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h3 class="text-lg font-semibold mb-4">Statistics</h3>
        <div class="space-y-4">
            <div class="flex justify-between">
                <span class="text-gray-500">Total Obligations</span>
                <span class="font-medium" id="total-obligations">0</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Total Receipts</span>
                <span class="font-medium" id="total-receipts">0</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Total Remittances</span>
                <span class="font-medium" id="total-remittances">0</span>
            </div>
            <div class="border-t pt-4 mt-4">
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Expected</span>
                    <span class="font-medium" id="total-expected">GHS 0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Received</span>
                    <span class="font-medium" id="total-received">GHS 0.00</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Total Remitted</span>
                    <span class="font-medium" id="total-remitted">GHS 0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="edit-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Edit Profile</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="profile-form" onsubmit="saveProfile(event)">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" id="edit-name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="edit-email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" id="edit-phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Save</button>
            </div>
        </form>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-200 p-6 mt-6">
    <h3 class="text-lg font-semibold mb-4">Change Password</h3>
    <form id="password-form" class="space-y-4 max-w-md">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
            <input type="password" name="current_password" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
            <input type="password" name="new_password" required min="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
            <input type="password" name="new_password_confirmation" required min="8" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-500 focus:border-gray-500">
        </div>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Update Password</button>
    </form>
    <div id="password-message" class="mt-4 hidden"></div>
</div>
@endsection

<script>
const currency = localStorage.getItem('currency') || 'GHS';

function formatCurrency(amount) {
    const symbols = { GHS: 'GHS ', USD: '$ ', EUR: '€ ', GBP: '£ ', NGN: '₦ ' };
    return (symbols[currency] || 'GHS ') + parseFloat(amount || 0).toFixed(2);
}

async function loadProfile() {
    try {
        const userId = {{ session('user_id', 0) }};
        const userName = '{{ session('user') }}';
        
        document.getElementById('user-name').textContent = userName;
        
        const [obRes, recRes, remRes, userRes] = await Promise.all([
            fetch('/api/v1/obligations'),
            fetch('/api/v1/receipts'),
            fetch('/api/v1/remittances'),
            userId > 0 ? fetch('/api/v1/users/' + userId) : Promise.reject()
        ]);
        
        const obs = await obRes.json();
        const recs = await recRes.json();
        const rems = await remRes.json();
        
        document.getElementById('total-obligations').textContent = obs.success ? obs.data.length : 0;
        document.getElementById('total-receipts').textContent = recs.success ? recs.data.length : 0;
        document.getElementById('total-remittances').textContent = rems.success ? rems.data.length : 0;
        
        if (obs.success) {
            const totalExpected = obs.data.reduce((sum, o) => sum + parseFloat(o.amount_expected || 0), 0);
            const totalReceived = obs.data.reduce((sum, o) => sum + parseFloat(o.amount_received || 0), 0);
            document.getElementById('total-expected').textContent = formatCurrency(totalExpected);
            document.getElementById('total-received').textContent = formatCurrency(totalReceived);
        }
        
        if (recs.success) {
            const totalRec = recs.data.reduce((sum, r) => sum + parseFloat(r.amount_received || 0), 0);
            document.getElementById('total-received').textContent = formatCurrency(totalRec);
        }
        
        if (rms.success) {
            const totalRem = rms.data.reduce((sum, r) => sum + parseFloat(r.amount_paid || 0), 0);
            document.getElementById('total-remitted').textContent = formatCurrency(totalRem);
        }
        
        if (userId > 0 && userRes.ok) {
            const user = await userRes.json();
            if (user.success && user.data) {
                document.getElementById('user-email').textContent = user.data.email || '-';
                document.getElementById('user-phone').textContent = user.data.phone || '-';
                document.getElementById('user-created').textContent = user.data.created_at ? new Date(user.data.created_at).toLocaleDateString('en-GB') : '-';
                document.getElementById('edit-name').value = user.data.name || '';
                document.getElementById('edit-email').value = user.data.email || '';
                document.getElementById('edit-phone').value = user.data.phone || '';
            }
        }
    } catch (error) {
        console.error('Error loading profile:', error);
    }
}

function openEditModal() {
    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('edit-modal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
    document.getElementById('edit-modal').classList.remove('flex');
}

async function saveProfile(e) {
    e.preventDefault();
    const userId = {{ session('user_id', 0) }};
    const form = document.getElementById('profile-form');
    const formData = new FormData(form);
    
    try {
        const response = await fetch('/api/v1/profile/' + userId, {
            method: 'PUT',
            headers: { 
                'Content-Type': 'application/json',
                'X-User-Id': userId
            },
            body: JSON.stringify({
                name: formData.get('name'),
                email: formData.get('email'),
                phone: formData.get('phone')
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeEditModal();
            document.getElementById('user-name').textContent = result.data.name;
            document.getElementById('user-email').textContent = result.data.email;
            document.getElementById('user-phone').textContent = result.data.phone || '-';
            alert('Profile updated successfully!');
        } else {
            alert(result.message || 'Error updating profile');
        }
    } catch (error) {
        alert('Error updating profile');
    }
}

document.getElementById('password-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const messageEl = document.getElementById('password-message');
    
    if (formData.get('new_password') !== formData.get('new_password_confirmation')) {
        messageEl.textContent = 'New passwords do not match';
        messageEl.className = 'mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded';
        messageEl.classList.remove('hidden');
        return;
    }
    
    try {
        const userId = {{ session('user_id', 0) }};
        const res = await fetch('/api/v1/profile/password', {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'X-User-Id': userId
            },
            body: JSON.stringify({
                current_password: formData.get('current_password'),
                new_password: formData.get('new_password')
            })
        });
        
        const result = await res.json();
        
        if (result.success) {
            messageEl.textContent = 'Password updated successfully';
            messageEl.className = 'mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded';
            this.reset();
        } else {
            messageEl.textContent = result.message || 'Failed to update password';
            messageEl.className = 'mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded';
        }
    } catch (error) {
        messageEl.textContent = 'An error occurred';
        messageEl.className = 'mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded';
    }
    
    messageEl.classList.remove('hidden');
});

loadProfile();
</script>
@extends('layouts.app')

@section('title', 'Users - SmartCash')
@section('header', 'User Management')

@section('content')
<div class="mb-6 flex justify-end">
    <button onclick="openModal()" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700 flex items-center gap-2">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Add User
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50">
            <tr>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Name</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Email</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Phone</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Created</th>
                <th class="text-left py-3 px-4 text-sm font-medium text-gray-500">Actions</th>
            </tr>
        </thead>
        <tbody id="users-table">
            <tr>
                <td colspan="5" class="py-4 px-4 text-center text-gray-500">Loading...</td>
            </tr>
        </tbody>
    </table>
</div>

<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4 sticky top-0 bg-white pb-2">
            <h3 class="text-lg font-semibold">Add User</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="user-form" onsubmit="saveUser(event)">
            <input type="hidden" name="id" id="user-id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone (optional)</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                    <input type="password" name="password" id="user-password" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

<script>
async function loadUsers() {
    try {
        const response = await fetch('/api/v1/users');
        const result = await response.json();
        const tbody = document.getElementById('users-table');
        
        if (result.success && result.data.length > 0) {
            tbody.innerHTML = result.data.map(user => `
                <tr class="border-b border-gray-100 hover:bg-gray-50">
                    <td class="py-3 px-4">${user.name}</td>
                    <td class="py-3 px-4">${user.email}</td>
                    <td class="py-3 px-4">${user.phone || '-'}</td>
                    <td class="py-3 px-4 text-sm text-gray-500">${user.created_at ? new Date(user.created_at).toLocaleDateString('en-GB') : '-'}</td>
                    <td class="py-3 px-4">
                        <button onclick="resetPassword(${user.id}, '${user.name}')" class="text-blue-600 hover:text-blue-800 mr-2">Reset Password</button>
                        <button onclick="deleteUser(${user.id}, '${user.name}')" class="text-red-600 hover:text-red-800">Delete</button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="py-4 px-4 text-center text-gray-500">No users found</td></tr>';
        }
    } catch (error) {
        console.error('Error loading users:', error);
    }
}

function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('user-id').value = '';
    document.getElementById('user-form').reset();
    document.getElementById('user-password').required = true;
    document.querySelector('#modal h3').textContent = 'Add User';
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

async function saveUser(e) {
    e.preventDefault();
    const form = document.getElementById('user-form');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    if (formData.get('password') !== formData.get('password_confirmation')) {
        alert('Passwords do not match');
        return;
    }
    
    const data = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone') || null,
    };
    
    if (formData.get('password')) {
        data.password = formData.get('password');
        data.password_confirmation = formData.get('password_confirmation');
    }
    
    try {
        const url = id ? `/api/v1/users/${id}` : '/api/v1/users';
        const method = id ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            loadUsers();
        } else {
            alert(result.message || 'Error saving user');
        }
    } catch (error) {
        alert('Error saving user');
    }
}

async function resetPassword(userId, userName) {
    const newPassword = prompt(`Enter new password for ${userName}:`);
    if (!newPassword) return;
    
    const confirmPassword = prompt('Confirm password:');
    if (newPassword !== confirmPassword) {
        alert('Passwords do not match');
        return;
    }
    
    try {
        const response = await fetch(`/api/v1/users/${userId}/reset-password`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ password: newPassword })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Password reset successfully');
        } else {
            alert(result.message || 'Error resetting password');
        }
    } catch (error) {
        alert('Error resetting password');
    }
}

async function deleteUser(userId, userName) {
    if (!confirm(`Delete user "${userName}"? This will also delete all their data.`)) return;
    
    try {
        const response = await fetch(`/api/v1/users/${userId}`, { method: 'DELETE' });
        const result = await response.json();
        
        if (result.success) {
            loadUsers();
        } else {
            alert(result.message || 'Error deleting user');
        }
    } catch (error) {
        alert('Error deleting user');
    }
}

document.addEventListener('DOMContentLoaded', loadUsers);
</script>
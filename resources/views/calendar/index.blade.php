@extends('layouts.app')

@section('title', 'Calendar - SmartCash')
@section('header', 'Calendar')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div class="flex gap-2">
        <button onclick="changeMonth(-1)" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
            ← Prev
        </button>
        <button onclick="changeMonth(0)" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
            Today
        </button>
        <button onclick="changeMonth(1)" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
            Next →
        </button>
        <span id="current-month" class="px-3 py-2 font-semibold"></span>
    </div>
    <button onclick="openModal()" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
        + Add Reminder
    </button>
</div>

<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <div class="grid grid-cols-7 bg-gray-50">
        <div class="py-2 text-center text-sm font-medium text-gray-500">Sun</div>
        <div class="py-2 text-center text-sm font-medium text-gray-500">Mon</div>
        <div class="py-2 text-center text-sm font-medium text-gray-500">Tue</div>
        <div class="py-2 text-center text-sm font-medium text-gray-500">Wed</div>
        <div class="py-2 text-center text-sm font-medium text-gray-500">Thu</div>
        <div class="py-2 text-center text-sm font-medium text-gray-500">Fri</div>
        <div class="py-2 text-center text-sm font-medium text-gray-500">Sat</div>
    </div>
    <div id="calendar-grid" class="grid grid-cols-7"></div>
</div>

<div class="mt-6 bg-white rounded-xl border border-gray-200 p-4">
    <h3 class="text-lg font-semibold mb-4">Upcoming Reminders</h3>
    <div id="upcoming-list" class="space-y-2"></div>
</div>
</div>

<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4 sticky top-0 bg-white pb-2">
            <h3 class="text-lg font-semibold">Add Reminder</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="reminder-form" onsubmit="saveReminder(event)">
            <input type="hidden" name="id" id="reminder-id" value="">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                    <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" name="reminder_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" name="reminder_time" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Repeat</label>
                    <select name="repeat_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                        <option value="">No repeat</option>
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <div id="repeat-until-field" class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Repeat Until</label>
                    <input type="date" name="repeat_until" max="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notify Additional Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="optional">
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
let currentDate = new Date();
let reminders = [];

async function loadReminders() {
    try {
        const userId = {{ session('user_id', 0) }};
        const url = userId > 0 ? `/api/v1/reminders?user_id=${userId}` : '/api/v1/reminders';
        const response = await fetch(url, {
            credentials: 'include',
            headers: { 'Accept': 'application/json', 'X-User-Id': smartcashUserId.toString() }
        });
        const result = await response.json();
        reminders = result.success ? result.data : [];
        renderCalendar();
        renderUpcoming();
    } catch (error) {
        console.error('Error loading reminders:', error);
    }
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    document.getElementById('current-month').textContent = 
        new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDay = firstDay.getDay();
    const daysInMonth = lastDay.getDate();
    
    const grid = document.getElementById('calendar-grid');
    grid.innerHTML = '';
    
    for (let i = 0; i < startDay; i++) {
        const empty = document.createElement('div');
        empty.className = 'min-h-[80px] border-b border-r border-gray-100 bg-gray-50';
        grid.appendChild(empty);
    }
    
    for (let day = 1; day <= daysInMonth; day++) {
        const cell = document.createElement('div');
        cell.className = 'min-h-[80px] border-b border-r border-gray-100 p-1';
        
        const dayNum = document.createElement('span');
        dayNum.className = 'text-sm font-medium';
        dayNum.textContent = day;
        cell.appendChild(dayNum);
        
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayReminders = reminders.filter(r => {
            const remindDate = new Date(r.reminder_at).toISOString().split('T')[0];
            return remindDate === dateStr;
        });
        
        dayReminders.forEach(r => {
            const badge = document.createElement('div');
            badge.className = 'text-xs bg-purple-100 text-purple-800 px-1 py-0.5 rounded mt-1 cursor-pointer truncate';
            badge.textContent = r.title;
            badge.onclick = () => editReminder(r);
            cell.appendChild(badge);
        });
        
        const addBtn = document.createElement('button');
        addBtn.className = 'text-xs text-purple-600 hover:text-purple-800 mt-1 hidden';
        addBtn.textContent = '+ Add';
        addBtn.onclick = () => {
            const date = new Date(year, month, day);
            const dateStr = date.toISOString().split('T')[0];
            document.querySelector('input[name="reminder_date"]').value = dateStr;
            document.querySelector('input[name="reminder_time"]').value = '09:00';
            openModal();
        };
        cell.appendChild(addBtn);
        cell.onmouseenter = () => addBtn.classList.remove('hidden');
        cell.onmouseleave = () => addBtn.classList.add('hidden');
        
        grid.appendChild(cell);
    }
    
    const totalCells = startDay + daysInMonth;
    const remainingCells = (7 - (totalCells % 7)) % 7;
    for (let i = 0; i < remainingCells; i++) {
        const empty = document.createElement('div');
        empty.className = 'min-h-[80px] border-b border-r border-gray-100 bg-gray-50';
        grid.appendChild(empty);
    }
}

function renderUpcoming() {
    const container = document.getElementById('upcoming-list');
    const now = new Date();
    
    const upcoming = reminders
        .filter(r => r.is_active && new Date(r.reminder_at) >= now)
        .sort((a, b) => new Date(a.reminder_at) - new Date(b.reminder_at))
        .slice(0, 10);
    
    if (upcoming.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No upcoming reminders</p>';
        return;
    }
    
    container.innerHTML = upcoming.map(r => `
        <div class="flex justify-between items-center py-2 border-b border-gray-100">
            <div>
                <p class="font-medium">${r.title}</p>
                <p class="text-sm text-gray-500">${r.formatted_reminder_at}${r.repeat_type ? ' (' + r.repeat_type + ')' : ''}</p>
            </div>
            <div class="flex gap-2">
                <button onclick="editReminder(${JSON.stringify(r).replace(/"/g, '&quot;')})" class="text-blue-600 hover:text-blue-800">Edit</button>
                <button onclick="deleteReminder(${r.id})" class="text-red-600 hover:text-red-800">Delete</button>
            </div>
        </div>
    `).join('');
}

function changeMonth(delta) {
    if (delta === 0) {
        currentDate = new Date();
    } else {
        currentDate.setMonth(currentDate.getMonth() + delta);
    }
    renderCalendar();
}

function openModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal').classList.add('flex');
    document.getElementById('reminder-id').value = '';
    document.getElementById('reminder-form').reset();
    document.querySelector('#modal h3').textContent = 'Add Reminder';
}

function closeModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal').classList.remove('flex');
}

function editReminder(reminder) {
    document.getElementById('reminder-id').value = reminder.id;
    document.querySelector('input[name="title"]').value = reminder.title;
    document.querySelector('textarea[name="description"]').value = reminder.description || '';
    
    const reminderDateTime = reminder.reminder_at.split(' ');
    document.querySelector('input[name="reminder_date"]').value = reminderDateTime[0];
    document.querySelector('input[name="reminder_time"]').value = reminderDateTime[1] ? reminderDateTime[1].substring(0, 5) : '09:00';
    
    document.querySelector('select[name="repeat_type"]').value = reminder.repeat_type || '';
    document.querySelector('input[name="repeat_until"]').value = reminder.repeat_until || '';
    document.querySelector('input[name="email"]').value = reminder.email || '';
    
    if (reminder.repeat_type) {
        document.getElementById('repeat-until-field').classList.remove('hidden');
    }
    
    document.querySelector('#modal h3').textContent = 'Edit Reminder';
    openModal();
}

document.querySelector('select[name="repeat_type"]').addEventListener('change', function() {
    const field = document.getElementById('repeat-until-field');
    if (this.value) {
        field.classList.remove('hidden');
    } else {
        field.classList.add('hidden');
    }
});

async function saveReminder(e) {
    e.preventDefault();
    const form = document.getElementById('reminder-form');
    const formData = new FormData(form);
    const id = formData.get('id');
    
    const userId = {{ session('user_id', 0) }};
    const reminderDate = formData.get('reminder_date');
    const reminderTime = formData.get('reminder_time') || '09:00';
    const reminderAt = reminderDate && reminderTime ? `${reminderDate} ${reminderTime}:00` : null;
    
    const data = {
        title: formData.get('title'),
        description: formData.get('description'),
        reminder_at: reminderAt,
        repeat_type: formData.get('repeat_type') || null,
        repeat_until: formData.get('repeat_until') || null,
        is_active: true,
        user_id: userId > 0 ? userId : null,
    };
    
    const url = id ? `/api/v1/reminders/${id}` : '/api/v1/reminders';
    const method = id ? 'PUT' : 'POST';
    
    try {
        const response = await fetch(url, {
            method: method,
            credentials: 'include',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-User-Id': smartcashUserId.toString() },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeModal();
            loadReminders();
        } else {
            alert(result.message || 'Error saving reminder');
        }
    } catch (error) {
        alert('Error saving reminder');
    }
}

async function deleteReminder(id) {
    if (!confirm('Delete this reminder?')) return;
    
    try {
        const response = await fetch(`/api/v1/reminders/${id}`, { 
            method: 'DELETE',
            credentials: 'include',
            headers: { 'X-User-Id': smartcashUserId.toString() }
        });
        const result = await response.json();
        
        if (result.success) {
            loadReminders();
        }
    } catch (error) {
        alert('Error deleting reminder');
    }
}

document.addEventListener('DOMContentLoaded', loadReminders);
</script>
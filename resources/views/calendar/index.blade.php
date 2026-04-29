@extends('layouts.app')

@section('title', 'Calendar - SmartCash')
@section('header', 'Calendar')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <div class="flex gap-2">
        <button id="prev-month" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
            ← Prev
        </button>
        <button id="today-btn" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
            Today
        </button>
        <button id="next-month" class="px-3 py-2 bg-gray-200 rounded hover:bg-gray-300">
            Next →
        </button>
        <span id="current-month" class="px-3 py-2 font-semibold"></span>
    </div>
    <button id="add-reminder-btn" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
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

<div id="modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4 max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4 sticky top-0 bg-white pb-2">
            <h3 id="modal-title" class="text-lg font-semibold">Add Reminder</h3>
            <button id="close-modal-x" class="text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <form id="reminder-form">
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
                    <input type="date" name="repeat_until" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notify Additional Email</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="optional">
                </div>
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" id="cancel-btn" class="px-4 py-2 text-gray-600 hover:text-gray-800">Cancel</button>
                <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-lg hover:bg-gray-700">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
let currentDate = new Date();
let reminders = [];

async function apiRequest(url, options = {}) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const defaultHeaders = {
        'Accept': 'application/json',
        'X-User-Id': typeof smartcashUserId !== 'undefined' ? smartcashUserId.toString() : ''
    };
    
    if (csrfToken) {
        defaultHeaders['X-CSRF-TOKEN'] = csrfToken;
    }
    
    const fetchOptions = {
        credentials: 'include',
        headers: { ...defaultHeaders }
    };
    
    if (options.method) {
        fetchOptions.method = options.method;
    }
    
    if (options.body) {
        fetchOptions.body = JSON.stringify(options.body);
        fetchOptions.headers['Content-Type'] = 'application/json';
    }
    
    return fetch(url, fetchOptions);
}

async function loadReminders() {
    try {
        const userId = {{ session('user_id', 0) }};
        const url = userId > 0 ? `/api/v1/reminders?user_id=${userId}` : '/api/v1/reminders';
        const response = await apiRequest(url);
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
    
    const monthEl = document.getElementById('current-month');
    if (monthEl) {
        monthEl.textContent = new Date(year, month).toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    }
    
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDay = firstDay.getDay();
    const daysInMonth = lastDay.getDate();
    
    const grid = document.getElementById('calendar-grid');
    if (!grid) return;
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
        addBtn.type = 'button';
        addBtn.className = 'text-xs text-purple-600 hover:text-purple-800 mt-1 hidden';
        addBtn.textContent = '+ Add';
        addBtn.onclick = () => {
            const date = new Date(year, month, day);
            const dateStr = date.toISOString().split('T')[0];
            const dateInput = document.querySelector('input[name="reminder_date"]');
            const timeInput = document.querySelector('input[name="reminder_time"]');
            if (dateInput) dateInput.value = dateStr;
            if (timeInput) timeInput.value = '09:00';
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
    if (!container) return;
    
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
                <button class="edit-btn text-blue-600 hover:text-blue-800" data-reminder='${JSON.stringify(r).replace(/'/g, "&#39;")}'>Edit</button>
                <button class="delete-btn text-red-600 hover:text-red-800" data-id="${r.id}">Delete</button>
            </div>
        </div>
    `).join('');
    
    container.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const reminder = JSON.parse(this.getAttribute('data-reminder').replace(/&#39;/g, "'"));
            editReminder(reminder);
        });
    });
    
    container.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            deleteReminder(this.getAttribute('data-id'));
        });
    });
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
    const modal = document.getElementById('modal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }
}

function closeModal() {
    const modal = document.getElementById('modal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
}

function editReminder(reminder) {
    const idInput = document.getElementById('reminder-id');
    const titleInput = document.querySelector('input[name="title"]');
    const descInput = document.querySelector('textarea[name="description"]');
    const repeatSelect = document.querySelector('select[name="repeat_type"]');
    const repeatUntilInput = document.querySelector('input[name="repeat_until"]');
    const emailInput = document.querySelector('input[name="email"]');
    const titleEl = document.getElementById('modal-title');
    
    if (idInput) idInput.value = reminder.id;
    if (titleInput) titleInput.value = reminder.title || '';
    if (descInput) descInput.value = reminder.description || '';
    
    const reminderDateTime = (reminder.reminder_at || '').split(' ');
    const dateInput = document.querySelector('input[name="reminder_date"]');
    const timeInput = document.querySelector('input[name="reminder_time"]');
    if (dateInput) dateInput.value = reminderDateTime[0] || '';
    if (timeInput) timeInput.value = reminderDateTime[1] ? reminderDateTime[1].substring(0, 5) : '09:00';
    
    if (repeatSelect) repeatSelect.value = reminder.repeat_type || '';
    if (repeatUntilInput) repeatUntilInput.value = reminder.repeat_until || '';
    if (emailInput) emailInput.value = reminder.email || '';
    
    const repeatField = document.getElementById('repeat-until-field');
    if (repeatField) {
        if (reminder.repeat_type) {
            repeatField.classList.remove('hidden');
        } else {
            repeatField.classList.add('hidden');
        }
    }
    
    if (titleEl) titleEl.textContent = 'Edit Reminder';
    openModal();
}

async function saveReminder(e) {
    e.preventDefault();
    const form = document.getElementById('reminder-form');
    if (!form) return;
    
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
        const response = await apiRequest(url, {
            method: method,
            body: data
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
        const response = await apiRequest(`/api/v1/reminders/${id}`, { 
            method: 'DELETE'
        });
        const result = await response.json();
        
        if (result.success) {
            loadReminders();
        }
    } catch (error) {
        alert('Error deleting reminder');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    const prevBtn = document.getElementById('prev-month');
    const todayBtn = document.getElementById('today-btn');
    const nextBtn = document.getElementById('next-month');
    const addBtn = document.getElementById('add-reminder-btn');
    const closeX = document.getElementById('close-modal-x');
    const cancelBtn = document.getElementById('cancel-btn');
    const form = document.getElementById('reminder-form');
    const repeatSelect = document.querySelector('select[name="repeat_type"]');
    const repeatField = document.getElementById('repeat-until-field');
    
    if (prevBtn) prevBtn.addEventListener('click', () => changeMonth(-1));
    if (todayBtn) todayBtn.addEventListener('click', () => changeMonth(0));
    if (nextBtn) nextBtn.addEventListener('click', () => changeMonth(1));
    if (addBtn) addBtn.addEventListener('click', openModal);
    if (closeX) closeX.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (form) form.addEventListener('submit', saveReminder);
    
    if (repeatSelect && repeatField) {
        repeatSelect.addEventListener('change', function() {
            if (this.value) {
                repeatField.classList.remove('hidden');
            } else {
                repeatField.classList.add('hidden');
            }
        });
    }
    
    loadReminders();
});
</script>
@endsection

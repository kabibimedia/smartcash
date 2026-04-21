@extends('layouts.app')

@section('title', 'Audit Trail - SmartCash')
@section('header', 'Audit Trail')

@section('content')
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <h3 class="text-lg font-semibold mb-4">Activity Log</h3>
    <div id="audit-list" class="space-y-3">
        <p class="text-gray-500">Loading...</p>
    </div>
</div>

<script>
async function loadAudits() {
    try {
        const response = await fetch('/api/v1/audits');
        const result = await response.json();
        const container = document.getElementById('audit-list');
        
        if (result.success && result.data.length > 0) {
            container.innerHTML = result.data.map(audit => {
                const actionColors = {
                    'create': 'bg-green-100 text-green-600',
                    'update': 'bg-blue-100 text-blue-600',
                    'delete': 'bg-red-100 text-red-600',
                };
                const color = actionColors[audit.action] || 'bg-gray-100 text-gray-600';
                const userName = audit.user?.name || 'System';
                const date = new Date(audit.created_at).toLocaleString();
                
                return `
                    <div class="flex justify-between items-center py-3 border-b border-gray-100">
                        <div>
                            <p class="font-medium">${userName}</p>
                            <p class="text-sm text-gray-500">${audit.action} ${audit.entity_type ? audit.entity_type.split('\\').pop() + ' #' + audit.entity_id : ''}</p>
                            <p class="text-xs text-gray-400">${date}</p>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-medium ${color}">${audit.action}</span>
                    </div>
                `;
            }).join('');
        } else {
            container.innerHTML = '<p class="text-gray-500">No activity recorded yet</p>';
        }
    } catch (error) {
        console.error('Error loading audits:', error);
    }
}

loadAudits();
</script>
@endsection
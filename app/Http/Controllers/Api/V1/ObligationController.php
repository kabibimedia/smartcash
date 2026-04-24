<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObligationRequest;
use App\Models\Obligation;
use App\Models\User;
use App\Notifications\ObligationCreatedNotification;
use App\Notifications\ObligationUpdatedNotification;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ObligationController extends Controller
{
    private function getUserId(Request $request): ?int
    {
        // Try X-User-Id header first (from JavaScript)
        $headerUserId = $request->header('X-User-Id');
        if ($headerUserId) {
            $id = (int) $headerUserId;
            if ($id > 0) {
                return $id;
            }
        }
        
        // Try cookie
        $userId = $request->cookie('smartcash_uid');
        if ($userId) {
            $id = (int) $userId;
            if ($id > 0) {
                return $id;
            }
        }
        
        // Fallback to session
        $sessionUserId = session('user_id');
        if ($sessionUserId) {
            $id = (int) $sessionUserId;
            if ($id > 0) {
                return $id;
            }
        }
        
        return null;
    }

    public function index(Request $request): JsonResponse
    {
        $userId = $this->getUserId($request);
        
        if (! $userId) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $obligations = Obligation::query()
            ->where('user_id', $userId)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('frequency'), fn ($q, $freq) => $q->where('frequency', $freq))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $obligations,
        ]);
    }

    public function store(StoreObligationRequest $request): JsonResponse
    {
        $userId = $this->getUserId($request);
        
        if (! $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please login again.'
            ], 401);
        }

        $validated = $request->validated();
        $validated['user_id'] = $userId;

        $obligation = Obligation::create($validated);

        AuditService::log('create', Obligation::class, $obligation->id, null, $validated);

        $emails = [];
        $user = User::find($userId);
        if ($user && $user->email) {
            $emails[] = $user->email;
        }
        if ($obligation->email && ! in_array($obligation->email, $emails)) {
            $emails[] = $obligation->email;
        }
        foreach ($emails as $email) {
            $obligation->notify(new ObligationCreatedNotification($obligation));
        }

        return response()->json([
            'success' => true,
            'message' => 'Obligation created successfully.',
            'data' => $obligation,
        ], 201);
    }

    public function show(Request $request, Obligation $obligation): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $obligation->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        return response()->json([
            'success' => true,
            'data' => $obligation->load('receipts'),
        ]);
    }

    public function update(Request $request, Obligation $obligation): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $obligation->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'amount_expected' => 'sometimes|numeric|min:0',
            'amount_received' => 'sometimes|numeric|min:0',
            'frequency' => 'sometimes|in:monthly,quarterly,one-time',
            'due_date' => 'sometimes|date',
            'notes' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $oldValues = $obligation->toArray();
        $obligation->update($validated);

        AuditService::log('update', Obligation::class, $obligation->id, $oldValues, $validated);

        $this->updateStatus($obligation);

        $emails = [];
        if ($obligation->user_id) {
            $user = User::find($obligation->user_id);
            if ($user && $user->email) {
                $emails[] = $user->email;
            }
        }
        if ($obligation->email && ! in_array($obligation->email, $emails)) {
            $emails[] = $obligation->email;
        }
        foreach ($emails as $email) {
            $obligation->notify(new ObligationUpdatedNotification($obligation));
        }

        return response()->json([
            'success' => true,
            'message' => 'Obligation updated successfully.',
            'data' => $obligation->fresh(),
        ]);
    }

    public function destroy(Request $request, Obligation $obligation): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $obligation->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $oldValues = $obligation->toArray();
        $obligation->delete();

        AuditService::log('delete', Obligation::class, $obligation->id, $oldValues, null);

        return response()->json([
            'success' => true,
            'message' => 'Obligation deleted successfully.',
        ]);
    }

    private function updateStatus(Obligation $obligation): void
    {
        if ($obligation->amount_received >= $obligation->amount_expected && $obligation->amount_expected > 0) {
            $obligation->update(['status' => 'received']);
        } elseif ($obligation->amount_received > 0) {
            $obligation->update(['status' => 'partially_paid']);
        } elseif ($obligation->due_date->isPast()) {
            $obligation->update(['status' => 'overdue']);
        }
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObligationRequest;
use App\Http\Requests\UpdateObligationRequest;
use App\Models\Obligation;
use App\Models\User;
use App\Notifications\ObligationCreatedNotification;
use App\Notifications\ObligationUpdatedNotification;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;

class ObligationController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = session('user_id');

        $obligations = Obligation::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(request('status'), fn ($q, $status) => $q->where('status', $status))
            ->when(request('frequency'), fn ($q, $freq) => $q->where('frequency', $freq))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $obligations,
        ]);
    }

    public function store(StoreObligationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $userId = session('user_id');
        if ($userId && $userId > 0) {
            $data['user_id'] = $userId;
        }

        $obligation = Obligation::create($data);

        AuditService::log('create', Obligation::class, $obligation->id, null, $data);

        $emails = [];

        if ($userId && $userId > 0) {
            $user = User::find($userId);
            if ($user && $user->email) {
                $emails[] = $user->email;
            }
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

    public function show(Obligation $obligation): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $obligation->load('receipts'),
        ]);
    }

    public function update(UpdateObligationRequest $request, Obligation $obligation): JsonResponse
    {
        $oldValues = $obligation->toArray();
        $obligation->update($request->validated());

        AuditService::log('update', Obligation::class, $obligation->id, $oldValues, $request->validated());

        $this->updateStatus($obligation);

        $emails = [];

        if ($obligation->user_id && $obligation->user_id > 0) {
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

    public function destroy(Obligation $obligation): JsonResponse
    {
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

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRemittanceRequest;
use App\Models\Obligation;
use App\Models\Remittance;
use App\Models\User;
use App\Notifications\RemittanceAddedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RemittanceController extends Controller
{
    private function getUserId(Request $request): ?int
    {
        $headerUserId = $request->header('X-User-Id');
        if ($headerUserId) {
            $id = (int) $headerUserId;
            if ($id > 0) {
                return $id;
            }
        }
        
        $userId = $request->cookie('smartcash_uid');
        if ($userId) {
            $id = (int) $userId;
            if ($id > 0) {
                return $id;
            }
        }
        
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

        $remittances = Remittance::query()
            ->where('user_id', $userId)
            ->when($request->query('from') && $request->query('to'), 
                fn ($q) => $q->whereBetween('date_paid', [$request->query('from'), $request->query('to')]))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $remittances->load(['receipt.obligation']),
        ]);
    }

    public function store(StoreRemittanceRequest $request): JsonResponse
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

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'_remittance_'.$image->getClientOriginalName();
            $path = $image->storeAs('remittances', $filename, 'public');
            $validated['image_path'] = $path;
        }

        $remittance = Remittance::create($validated);

        $obligation = $remittance->receipt->obligation;
        $totalRemitted = $obligation->receipts()->with('remittances')->get()->sum(fn ($r) => $r->remittances()->sum('amount_paid'));

        if ($totalRemitted >= $obligation->amount_expected && $obligation->amount_expected > 0) {
            $obligation->update(['status' => 'remitted']);
        }

        $emails = [];
        $user = User::find($userId);
        if ($user && $user->email) {
            $emails[] = $user->email;
        }
        if ($obligation->email && ! in_array($obligation->email, $emails)) {
            $emails[] = $obligation->email;
        }
        if ($remittance->email && ! in_array($remittance->email, $emails)) {
            $emails[] = $remittance->email;
        }
        foreach ($emails as $email) {
            $obligation->notify(new RemittanceAddedNotification($obligation, $remittance));
        }

        return response()->json([
            'success' => true,
            'message' => 'Remittance created successfully.',
            'data' => $remittance->load('receipt.obligation'),
        ], 201);
    }

    public function show(Request $request, Remittance $remittance): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $remittance->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        return response()->json([
            'success' => true,
            'data' => $remittance->load('receipt.obligation'),
        ]);
    }

    public function update(Request $request, Remittance $remittance): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $remittance->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'receipt_id' => 'sometimes|exists:receipts,id',
            'amount_paid' => 'sometimes|numeric|min:0',
            'date_paid' => 'sometimes|date',
            'payment_method' => 'sometimes|in:cash,bank_transfer,mobile_money,cheque,other',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'_remittance_'.$image->getClientOriginalName();
            $path = $image->storeAs('remittances', $filename, 'public');
            $validated['image_path'] = $path;
        }

        $remittance->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Remittance updated successfully.',
            'data' => $remittance->fresh()->load('receipt.obligation'),
        ]);
    }

    public function destroy(Request $request, Remittance $remittance): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $remittance->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $obligation = $remittance->receipt->obligation;
        $remittance->delete();

        $totalRemitted = $obligation->receipts()->with('remittances')->get()->sum(fn ($r) => $r->remittances()->sum('amount_paid'));

        if ($totalRemitted < $obligation->amount_expected) {
            $obligation->update(['status' => 'received']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Remittance deleted successfully.',
        ]);
    }
}
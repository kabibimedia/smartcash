<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReceiptRequest;
use App\Models\Obligation;
use App\Models\Receipt;
use App\Models\User;
use App\Notifications\ReceiptAddedNotification;
use Illuminate\Http\JsonResponse;

class ReceiptController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = session('user_id');

        $receipts = Receipt::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
            ->when(request('obligation_id'), fn ($q, $id) => $q->where('obligation_id', $id))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $receipts->load('obligation'),
        ]);
    }

    public function store(StoreReceiptRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = session('user_id');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'_receipt_'.$image->getClientOriginalName();
            $path = $image->storeAs('receipts', $filename, 'public');
            $data['image_path'] = $path;
        }

        $receipt = Receipt::create($data);

        $obligation = $receipt->obligation;
        $obligation->amount_received = $obligation->receipts()->sum('amount_received');
        $obligation->save();
        $this->updateObligationStatus($obligation);

        $emails = [];

        if ($receipt->user_id && $receipt->user_id > 0) {
            $user = User::find($receipt->user_id);
            if ($user && $user->email) {
                $emails[] = $user->email;
            }
        }

        if ($obligation->email && ! in_array($obligation->email, $emails)) {
            $emails[] = $obligation->email;
        }

        if ($receipt->email && ! in_array($receipt->email, $emails)) {
            $emails[] = $receipt->email;
        }

        foreach ($emails as $email) {
            $obligation->notify(new ReceiptAddedNotification($obligation, $receipt));
        }

        return response()->json([
            'success' => true,
            'message' => 'Receipt created successfully.',
            'data' => $receipt->load('obligation'),
        ], 201);
    }

    public function show(Receipt $receipt): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $receipt->load(['obligation', 'remittances']),
        ]);
    }

    public function update(\Illuminate\Http\Request $request, Receipt $receipt): JsonResponse
    {
        // Skip validation for now, just get all data
        $data = $request->all();
        
        // Remove empty/unnecessary fields
        unset($data['_token'], $data['_method'], $data['id']);
        
        // Handle empty image
        if (isset($data['image']) && ($data['image'] instanceof \Illuminate\Http\UploadedFile)) {
            if ($data['image']->getSize() === 0) {
                unset($data['image']);
            }
        } else {
            unset($data['image']);
        }
        
        // Convert empty strings to null
        foreach ($data as $key => $value) {
            if ($value === '' || $value === null) {
                $data[$key] = null;
            }
        }
        
        $receipt->update($data);

        $obligation = $receipt->obligation;
        $obligation->amount_received = $obligation->receipts()->sum('amount_received');
        $obligation->save();
        $this->updateObligationStatus($obligation);

        return response()->json([
            'success' => true,
            'message' => 'Receipt updated successfully.',
            'data' => $receipt->fresh()->load('obligation'),
        ]);
    }

    public function destroy(Receipt $receipt): JsonResponse
    {
        $obligation = $receipt->obligation;
        $receipt->delete();

        $obligation->amount_received = $obligation->receipts()->sum('amount_received');
        $obligation->save();
        $this->updateObligationStatus($obligation);

        return response()->json([
            'success' => true,
            'message' => 'Receipt deleted successfully.',
        ]);
    }

    private function updateObligationStatus(Obligation $obligation): void
    {
        $totalReceived = $obligation->receipts()->sum('amount_received');

        if ($totalReceived >= $obligation->amount_expected && $obligation->amount_expected > 0) {
            $obligation->update(['status' => 'received']);
        } elseif ($totalReceived > 0) {
            $obligation->update(['status' => 'partially_paid']);
        } elseif ($obligation->due_date->isPast()) {
            $obligation->update(['status' => 'overdue']);
        } else {
            $obligation->update(['status' => 'pending']);
        }
    }
}

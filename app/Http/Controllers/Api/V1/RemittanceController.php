<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRemittanceRequest;
use App\Models\Obligation;
use App\Models\Remittance;
use App\Models\User;
use App\Notifications\RemittanceAddedNotification;
use Illuminate\Http\JsonResponse;

class RemittanceController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = session('user_id');

        $remittances = Remittance::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
            ->when(request('from') && request('to'), fn ($q) => $q->whereBetween('date_paid', [request('from'), request('to')]))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $remittances->load(['receipt.obligation']),
        ]);
    }

    public function store(StoreRemittanceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = session('user_id');

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'_remittance_'.$image->getClientOriginalName();
            $path = $image->storeAs('remittances', $filename, 'public');
            $data['image_path'] = $path;
        }

        $remittance = Remittance::create($data);

        $obligation = $remittance->receipt->obligation;
        $totalRemitted = $obligation->receipts()->with('remittances')->get()->sum(fn ($r) => $r->remittances()->sum('amount_paid'));

        if ($totalRemitted >= $obligation->amount_expected && $obligation->amount_expected > 0) {
            $obligation->update(['status' => 'remitted']);
        }

        $emails = [];

        if ($remittance->user_id && $remittance->user_id > 0) {
            $user = User::find($remittance->user_id);
            if ($user && $user->email) {
                $emails[] = $user->email;
            }
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

    public function show(Remittance $remittance): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $remittance->load('receipt.obligation'),
        ]);
    }

    public function update(StoreRemittanceRequest $request, Remittance $remittance): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $filename = time().'_remittance_'.$image->getClientOriginalName();
            $path = $image->storeAs('remittances', $filename, 'public');
            $data['image_path'] = $path;
        }

        $remittance->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Remittance updated successfully.',
            'data' => $remittance->fresh()->load('receipt.obligation'),
        ]);
    }

    public function destroy(Remittance $remittance): JsonResponse
    {
        $obligation = $remittance->receipt->obligation;
        $remittance->delete();

        $totalRemitted = Obligation::query()
            ->whereHas('receipts.remittances', fn ($q) => $q->where('id', '!=', $remittance->id))
            ->with('receipts.remittances')
            ->get()
            ->sum(fn ($o) => $o->receipts->sum(fn ($r) => $r->remittances->sum('amount_paid')));

        if ($totalRemitted < $obligation->amount_expected) {
            $obligation->update(['status' => 'received']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Remittance deleted successfully.',
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRemittanceRequest;
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
                'message' => 'Authentication required. Please login again.',
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

        // Try JSON first, then manual parse
        $data = $request->json()->all();

        if (empty($data)) {
            $rawInput = file_get_contents('php://input');
            if (str_contains($rawInput, 'form-data')) {
                preg_match_all('/name="([^"]+)"[\s\S]*?\n\n([^\n]+?)(?=\r?\n|\r|$)/m', $rawInput, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    $key = $match[1];
                    $value = trim($match[2]);
                    if ($value !== '' && $key !== 'id' && $key !== 'image' && ! str_starts_with($value, '------') && ! str_contains($value, 'boundary')) {
                        $data[$key] = $value;
                    }
                }
            }
        }

        if (empty($data)) {
            return response()->json(['success' => false, 'message' => 'No data']);
        }

        // Clean email field
        if (isset($data['email'])) {
            $email = $data['email'];
            if (empty($email) || strlen($email) > 100 || str_contains($email, 'boundary') || str_starts_with($email, '------')) {
                unset($data['email']);
            }
        }

        if ($request->hasFile('image') && $request->file('image')->getSize() > 0) {
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

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReminderRequest;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReminderController extends Controller
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

        $reminders = Reminder::query()
            ->where('user_id', $userId)
            ->orderBy('reminder_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reminders,
        ]);
    }

    public function store(StoreReminderRequest $request): JsonResponse
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

        $reminder = Reminder::create($validated);

        $emails = [];
        $user = User::find($userId);
        if ($user && $user->email) {
            $emails[] = $user->email;
        }
        if ($reminder->email && ! in_array($reminder->email, $emails)) {
            $emails[] = $reminder->email;
        }
        foreach ($emails as $email) {
            $reminder->notify(new ReminderNotification($reminder));
        }

        return response()->json([
            'success' => true,
            'message' => 'Reminder created successfully.',
            'data' => $reminder,
        ], 201);
    }

    public function show(Request $request, Reminder $reminder): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $reminder->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }
        return response()->json([
            'success' => true,
            'data' => $reminder,
        ]);
    }

    public function update(StoreReminderRequest $request, Reminder $reminder): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $reminder->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $reminder->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Reminder updated successfully.',
            'data' => $reminder->fresh(),
        ]);
    }

    public function destroy(Request $request, Reminder $reminder): JsonResponse
    {
        $userId = $this->getUserId($request);
        if ($userId && $reminder->user_id !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $reminder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reminder deleted successfully.',
        ]);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReminderRequest;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Http\JsonResponse;

class ReminderController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = session('user_id');

        if (! $userId && request()->has('user_id')) {
            $userId = request('user_id');
        }

        $reminders = Reminder::query()
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->orderBy('reminder_at', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $reminders,
        ]);
    }

    public function store(StoreReminderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $userId = session('user_id');

        if (! $userId && $request->has('user_id')) {
            $userId = $request->input('user_id');
        }

        if ($userId && $userId > 0) {
            $data['user_id'] = $userId;
        }

        $reminder = Reminder::create($data);

        $emails = [];

        if ($userId && $userId > 0) {
            $user = User::find($userId);
            if ($user && $user->email) {
                $emails[] = $user->email;
            }
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

    public function show(Reminder $reminder): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $reminder,
        ]);
    }

    public function update(StoreReminderRequest $request, Reminder $reminder): JsonResponse
    {
        $reminder->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Reminder updated successfully.',
            'data' => $reminder->fresh(),
        ]);
    }

    public function destroy(Reminder $reminder): JsonResponse
    {
        $reminder->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reminder deleted successfully.',
        ]);
    }
}

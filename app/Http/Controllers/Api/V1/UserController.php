<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function show(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $userId = session('user_id');

        if (! $userId) {
            $userId = $request->header('X-User-Id');
        }

        if (! $userId || $userId != $id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $request->validate([
            'surname' => 'sometimes|string|max:255',
            'first_name' => 'sometimes|string|max:255',
            'other_names' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$id,
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['surname', 'first_name', 'other_names', 'date_of_birth', 'email', 'phone']));
        session(['user' => $user->first_name . ' ' . $user->surname]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
            'data' => $user,
        ]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $userId = session('user_id');

        if (! $userId) {
            $userId = $request->header('X-User-Id');
        }

        if (! $userId || $userId == 0) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = User::find($userId);

        if (! $user || ! Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect'], 422);
        }

        $user->update(['password' => Hash::make($request->new_password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully',
        ]);
    }
}

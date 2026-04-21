<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obligation;
use App\Models\Receipt;
use App\Models\Reminder;
use App\Models\Remittance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user,
        ], 201);
    }

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
        $user = User::find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,'.$id,
            'phone' => 'nullable|string|max:20',
        ]);

        $data = $request->only(['name', 'email', 'phone']);
        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        if ($user->email === 'admin@smartcash.com') {
            return response()->json(['success' => false, 'message' => 'Cannot delete admin user'], 422);
        }

        Obligation::where('user_id', $id)->delete();
        Receipt::where('user_id', $id)->delete();
        Remittance::where('user_id', $id)->delete();
        Reminder::where('user_id', $id)->delete();

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.',
        ]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $request->validate([
            'password' => 'required|string|min:8',
        ]);

        $user->update(['password' => Hash::make($request->password)]);

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully.',
        ]);
    }
}

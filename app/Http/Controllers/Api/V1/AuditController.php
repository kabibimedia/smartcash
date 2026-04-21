<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Audit;
use Illuminate\Http\JsonResponse;

class AuditController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = session('user_id');
        $userName = session('user');

        $audits = Audit::query()
            ->when($userName !== 'Admin', fn ($q) => $q->where('user_id', $userId))
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->load('user');

        return response()->json([
            'success' => true,
            'data' => $audits,
        ]);
    }
}

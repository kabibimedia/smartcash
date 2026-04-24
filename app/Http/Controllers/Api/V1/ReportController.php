<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obligation;
use App\Models\Remittance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
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
        
        $queryUserId = $request->query('user_id');
        if ($queryUserId) {
            $id = (int) $queryUserId;
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

    public function monthly(Request $request): JsonResponse
    {
        $userId = $this->getUserId($request);
        
        if (! $userId) {
            return response()->json([
                'success' => true,
                'data' => [
                    'total_expected' => 0,
                    'total_received' => 0,
                    'total_paid' => 0,
                    'outstanding' => 0,
                    'late_payments' => 0,
                ],
            ]);
        }
        
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $obligations = Obligation::query()
            ->where('user_id', $userId)
            ->whereBetween('due_date', [$startDate, $endDate])
            ->get();

        $totalExpected = (float) $obligations->sum('amount_expected');
        $totalReceived = (float) $obligations->sum('amount_received');
        $totalPaid = $obligations->flatMap(fn ($o) => $o->receipts)->flatMap(fn ($r) => $r->remittances)->sum('amount_paid');

        $outstanding = $totalExpected - $totalReceived;
        $latePayments = $obligations->where('status', 'overdue')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'month' => $month,
                'year' => $year,
                'total_expected' => $totalExpected,
                'total_received' => $totalReceived,
                'total_paid' => $totalPaid,
                'outstanding' => $outstanding,
                'late_payments' => $latePayments,
            ],
        ]);
    }

    public function statement(Request $request): JsonResponse
    {
        $userId = $this->getUserId($request);
        
        if (! $userId) {
            return response()->json([
                'success' => true,
                'data' => [
                    'obligations' => [],
                    'receipts' => [],
                    'remittances' => [],
                    'summary' => [
                        'total_expected' => 0,
                        'total_received' => 0,
                        'total_remitted' => 0,
                    ],
                ],
            ]);
        }
        
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $type = $request->input('type', 'all');
        
        $today = now()->toDateString();
        if ($from > $today) $from = $today;
        if ($to > $today) $to = $today;

        $obligations = Obligation::query()
            ->where('user_id', $userId)
            ->whereDate('due_date', '>=', $from)
            ->whereDate('due_date', '<=', $to)
            ->with(['receipts', 'receipts.remittances'])
            ->get();

        $receipts = \App\Models\Receipt::query()
            ->whereHas('obligation', fn ($q) => $q->where('user_id', $userId))
            ->whereDate('date_received', '>=', $from)
            ->whereDate('date_received', '<=', $to)
            ->with(['obligation', 'remittances'])
            ->get();

        $remittances = \App\Models\Remittance::query()
            ->whereHas('receipt.obligation', fn ($q) => $q->where('user_id', $userId))
            ->whereDate('date_paid', '>=', $from)
            ->whereDate('date_paid', '<=', $to)
            ->with(['receipt.obligation'])
            ->get();

        $statement = $obligations->map(function ($obligation) {
            $receipts = $obligation->receipts;
            $remittances = $receipts->flatMap(fn ($r) => $r->remittances);

            return [
                'obligation' => [
                    'id' => $obligation->id,
                    'title' => $obligation->title,
                    'due_date' => $obligation->due_date,
                    'formatted_due_date' => $obligation->formatted_due_date,
                    'amount_expected' => $obligation->amount_expected,
                ],
                'amount_received' => $obligation->amount_received,
                'amount_remitted' => $remittances->sum('amount_paid'),
                'received' => $receipts->map(fn ($r) => [
                    'id' => $r->id,
                    'amount' => $r->amount_received,
                    'date' => $r->date_received,
                    'method' => $r->payment_method,
                ]),
                'remitted' => $remittances->map(fn ($r) => [
                    'id' => $r->id,
                    'amount' => $r->amount_paid,
                    'date' => $r->date_paid,
                    'method' => $r->payment_method,
                    'reference' => $r->reference,
                ]),
                'status' => $obligation->status,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'period' => ['from' => $from, 'to' => $to, 'type' => $type],
                'obligations' => $statement,
                'receipts' => $receipts->map(fn ($r) => [
                    'id' => $r->id,
                    'obligation_title' => $r->obligation?->title,
                    'amount' => $r->amount_received,
                    'date' => $r->date_received,
                    'method' => $r->payment_method,
                    'reference' => $r->reference,
                ]),
                'remittances' => $remittances->map(fn ($r) => [
                    'id' => $r->id,
                    'obligation_title' => $r->receipt?->obligation?->title,
                    'amount' => $r->amount_paid,
                    'date' => $r->date_paid,
                    'method' => $r->payment_method,
                    'reference' => $r->reference,
                ]),
                'summary' => [
                    'total_expected' => $obligations->sum('amount_expected'),
                    'total_received' => $obligations->sum('amount_received'),
                    'total_remitted' => $remittances->sum('amount_paid'),
                ],
            ],
        ]);
    }

    public function outstanding(): JsonResponse
    {
        $userId = $this->getUserId(request());

        if (! $userId) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $obligations = Obligation::query()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'partially_paid', 'overdue'])
            ->with('receipts')
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'amount_expected' => $o->amount_expected,
                'amount_received' => $o->amount_received,
                'outstanding' => $o->outstanding,
                'due_date' => $o->due_date,
                'formatted_due_date' => $o->formatted_due_date,
                'status' => $o->status,
            ]);

        return response()->json([
            'success' => true,
            'data' => $obligations,
        ]);
    }

    public function overdue(): JsonResponse
    {
        $userId = $this->getUserId(request());

        if (! $userId) {
            return response()->json([
                'success' => true,
                'data' => [],
            ]);
        }

        $obligations = Obligation::query()
            ->where('user_id', $userId)
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['received', 'remitted'])
            ->with('receipts')
            ->get()
            ->map(fn ($o) => [
                'id' => $o->id,
                'title' => $o->title,
                'amount_expected' => $o->amount_expected,
                'amount_received' => $o->amount_received,
                'outstanding' => $o->outstanding,
                'due_date' => $o->due_date,
                'formatted_due_date' => $o->formatted_due_date,
                'days_overdue' => $o->due_date->diffInDays(now()),
            ]);

        return response()->json([
            'success' => true,
            'data' => $obligations,
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $userId = $this->getUserId(request());
        
        if (! $userId) {
            return response()->json([
                'success' => true,
                'data' => [
                    'totals' => [
                        'expected' => 0,
                        'received' => 0,
                        'remitted' => 0,
                        'outstanding' => 0,
                    ],
                    'counts' => [
                        'pending' => 0,
                        'overdue' => 0,
                        'received' => 0,
                        'remitted' => 0,
                    ],
                ],
            ]);
        }

        $obligations = Obligation::query()
            ->where('user_id', $userId);

        $totalExpected = (float) $obligations->sum('amount_expected');
        $totalReceived = (float) $obligations->sum('amount_received');
        $totalRemitted = Remittance::query()
            ->where('user_id', $userId)
            ->sum('amount_paid');

        $pendingCount = (clone $obligations)->where('status', 'pending')->count();
        $overdueCount = (clone $obligations)->where('status', 'overdue')->count();
        $receivedCount = (clone $obligations)->where('status', 'received')->count();
        $remittedCount = (clone $obligations)->where('status', 'remitted')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'expected' => $totalExpected,
                    'received' => $totalReceived,
                    'remitted' => $totalRemitted,
                    'outstanding' => $totalExpected - $totalReceived,
                ],
                'counts' => [
                    'pending' => $pendingCount,
                    'overdue' => $overdueCount,
                    'received' => $receivedCount,
                    'remitted' => $remittedCount,
                ],
            ],
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $userId = $this->getUserId($request);
        
        if (! $userId) {
            $obligations = collect();
        } else {
            $from = $request->input('from', now()->startOfYear()->toDateString());
            $to = $request->input('to', now()->toDateString());
            $obligations = Obligation::query()
                ->where('user_id', $userId)
                ->whereDate('due_date', '>=', $from)
                ->whereDate('due_date', '<=', $to)
                ->with(['receipts', 'receipts.remittances'])
                ->get();
        }

        $callback = function () use ($obligations) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['SmartCash - Revenue Report']);

            foreach ($obligations as $obligation) {
                $totalRemitted = $obligation->receipts->flatMap(fn ($r) => $r->remittances)->sum('amount_paid');

                fputcsv($handle, [
                    $obligation->id,
                    $obligation->title,
                    $obligation->due_date,
                    $obligation->amount_expected,
                    $obligation->amount_received,
                    $totalRemitted,
                    $obligation->status,
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="smartcash-report-'.date('Y-m-d').'.csv"',
        ]);
    }

    public function exportPdf(Request $request)
    {
        $userId = $this->getUserId($request);
        
        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to = $request->input('to', now()->toDateString());
        
        if (! $userId) {
            $obligations = collect();
        } else {
            $obligations = Obligation::query()
                ->where('user_id', $userId)
                ->whereDate('due_date', '>=', $from)
                ->whereDate('due_date', '<=', $to)
                ->with(['receipts', 'receipts.remittances'])
                ->get();
        }

        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f5f5f5; }
        </style></head><body>
        <h1>SmartCash Report</h1>
        <p>Period: '.$from.' to '.$to.'</p>
        <table>
            <tr>
                <th>Title</th><th>Due Date</th><th>Expected</th><th>Received</th><th>Status</th>
            </tr>';

        foreach ($obligations as $obligation) {
            $totalRemitted = $obligation->receipts->flatMap(fn ($r) => $r->remittances)->sum('amount_paid');
            $html .= '<tr>
                <td>'.$obligation->title.'</td>
                <td>'.$obligation->due_date.'</td>
                <td>'.$obligation->amount_expected.'</td>
                <td>'.$obligation->amount_received.'</td>
                <td>'.$obligation->status.'</td>
            </tr>';
        }

        $html .= '</table></body></html>';

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}
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
    public function monthly(Request $request): JsonResponse
    {
        $userId = session('user_id');
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $obligations = Obligation::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
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
        $userId = session('user_id');
        $from = $request->input('from', now()->startOfYear());
        $to = $request->input('to', now()->endOfMonth());

        $obligations = Obligation::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
            ->whereBetween('due_date', [$from, $to])
            ->get();

        $statement = $obligations->map(function ($obligation) {
            $receipts = $obligation->receipts;
            $remittances = $receipts->flatMap(fn ($r) => $r->remittances);

            return [
                'obligation' => [
                    'id' => $obligation->id,
                    'title' => $obligation->title,
                    'due_date' => $obligation->due_date,
                    'amount_expected' => $obligation->amount_expected,
                ],
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
                'period' => ['from' => $from, 'to' => $to],
                'obligations' => $statement,
                'summary' => [
                    'total_expected' => $obligations->sum('amount_expected'),
                    'total_received' => $obligations->sum('amount_received'),
                    'total_remitted' => $obligations->flatMap(fn ($o) => $o->receipts)->flatMap(fn ($r) => $r->remittances)->sum('amount_paid'),
                ],
            ],
        ]);
    }

    public function outstanding(): JsonResponse
    {
        $userId = session('user_id');

        $obligations = Obligation::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
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
                'status' => $o->status,
            ]);

        return response()->json([
            'success' => true,
            'data' => $obligations,
        ]);
    }

    public function overdue(): JsonResponse
    {
        $userId = session('user_id');

        $obligations = Obligation::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
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
                'days_overdue' => $o->due_date->diffInDays(now()),
            ]);

        return response()->json([
            'success' => true,
            'data' => $obligations,
        ]);
    }

    public function dashboard(): JsonResponse
    {
        $userId = session('user_id');

        $obligations = Obligation::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id));

        $totalExpected = (float) $obligations->sum('amount_expected');
        $totalReceived = (float) $obligations->sum('amount_received');
        $totalRemitted = Remittance::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
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
        $userId = session('user_id');
        $from = $request->input('from', now()->startOfYear());
        $to = $request->input('to', now()->endOfMonth());

        $obligations = Obligation::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
            ->whereBetween('due_date', [$from, $to])
            ->with(['receipts', 'receipts.remittances'])
            ->get();

        $headers = ['ID', 'Title', 'Due Date', 'Expected', 'Received', 'Paid', 'Status', 'Receipt Date', 'Receipt Amount', 'Remittance Date', 'Remittance Amount'];

        $callback = function () use ($obligations) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($handle, ['SmartCash - Revenue Report']);

            foreach ($obligations as $obligation) {
                $received = $obligation->receipts->first();
                $remitted = $obligation->receipts->flatMap(fn ($r) => $r->remittances)->first();

                $totalRemitted = $obligation->receipts->flatMap(fn ($r) => $r->remittances)->sum('amount_paid');

                fputcsv($handle, [
                    $obligation->id,
                    $obligation->title,
                    $obligation->due_date,
                    $obligation->amount_expected,
                    $obligation->amount_received,
                    $totalRemitted,
                    $obligation->status,
                    $received?->date_received ?? '',
                    $received?->amount_received ?? '',
                    $remitted?->date_paid ?? '',
                    $remitted?->amount_paid ?? '',
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
        $userId = session('user_id');
        $from = $request->input('from', now()->startOfYear());
        $to = $request->input('to', now()->endOfMonth());

        $obligations = Obligation::query()
            ->when($userId, fn ($q, $id) => $q->where('user_id', $id))
            ->whereBetween('due_date', [$from, $to])
            ->with(['receipts', 'receipts.remittances'])
            ->get();

        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            h1 { color: #333; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f5f5f5; }
            .status { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
            .status-pending { background: #e5e5e5; }
            .status-received { background: #d4edda; }
            .status-remitted { background: #cce5ff; }
            .status-overdue { background: #f8d7da; }
        </style></head><body>
        <h1>SmartCash Report</h1>
        <p>Period: '.$from.' to '.$to.'</p>
        <table>
            <tr>
                <th>Title</th>
                <th>Due Date</th>
                <th>Expected</th>
                <th>Received</th>
                <th>Paid</th>
                <th>Status</th>
            </tr>';

        foreach ($obligations as $obligation) {
            $totalRemitted = $obligation->receipts->flatMap(fn ($r) => $r->remittances)->sum('amount_paid');
            $html .= '<tr>
                <td>'.$obligation->title.'</td>
                <td>'.$obligation->due_date.'</td>
                <td>'.$obligation->amount_expected.'</td>
                <td>'.$obligation->amount_received.'</td>
                <td>'.$totalRemitted.'</td>
                <td><span class="status status-'.$obligation->status.'">'.$obligation->status.'</span></td>
            </tr>';
        }

        $html .= '</table></body></html>';

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}

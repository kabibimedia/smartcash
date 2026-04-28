<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Obligation;
use App\Models\Receipt;
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

        // Group by currency
        $byCurrency = $obligations->groupBy('currency')->map(function ($group, $curr) {
            $receipts = $group->flatMap(fn ($o) => $o->receipts);
            $paid = $receipts->flatMap(fn ($r) => $r->remittances)->sum('amount_paid');
            $late = $group->where('status', 'overdue')->count();
            
            return [
                'currency' => $curr ?? 'GHS',
                'expected' => (float) $group->sum('amount_expected'),
                'received' => (float) $group->sum('amount_received'),
                'paid' => (float) $paid,
                'late_payments' => $late,
            ];
        })->values();

        $outstanding = $totalExpected - $totalReceived;
        $latePayments = $obligations->where('status', 'overdue')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_expected' => $totalExpected,
                'total_received' => $totalReceived,
                'total_paid' => $totalPaid,
                'outstanding' => $outstanding,
                'late_payments' => $latePayments,
                'by_currency' => $byCurrency,
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
        if ($from > $today) {
            $from = $today;
        }
        if ($to > $today) {
            $to = $today;
        }

        $obligations = Obligation::query()
            ->where('user_id', $userId)
            ->whereDate('due_date', '>=', $from)
            ->whereDate('due_date', '<=', $to)
            ->with(['receipts', 'receipts.remittances'])
            ->get();

        $receipts = Receipt::query()
            ->whereHas('obligation', fn ($q) => $q->where('user_id', $userId))
            ->whereDate('date_received', '>=', $from)
            ->whereDate('date_received', '<=', $to)
            ->with(['obligation', 'remittances'])
            ->get();

        $remittances = Remittance::query()
            ->whereHas('receipt.obligation', fn ($q) => $q->where('user_id', $userId))
            ->whereDate('date_paid', '>=', $from)
            ->whereDate('date_paid', '<=', $to)
            ->with(['receipt.obligation'])
            ->get();

        $statement = $obligations->map(function ($obligation) {
            $receipts = $obligation->receipts;
            $remittances = $receipts->flatMap(fn ($r) => $r->remittances);
            $currency = $obligation->currency ?? 'GHS';

            return [
                'obligation' => [
                    'id' => $obligation->id,
                    'title' => $obligation->title,
                    'due_date' => $obligation->due_date,
                    'formatted_due_date' => $obligation->formatted_due_date,
                    'amount_expected' => $obligation->amount_expected,
                    'currency' => $currency,
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
                'currency' => $o->currency ?? 'GHS',
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
                'days_overdue' => (int) $o->due_date->diffInDays(now()),
                'currency' => $o->currency ?? 'GHS',
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
        $symbols = ['GHS' => '₵', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦'];

        if (! $userId) {
            $items = collect();
        } else {
            $from = $request->input('from', now()->startOfYear()->toDateString());
            $to = $request->input('to', now()->toDateString());
            $type = $request->input('type', 'all');

            $obligations = Obligation::query()
                ->where('user_id', $userId)
                ->whereDate('due_date', '>=', $from)
                ->whereDate('due_date', '<=', $to)
                ->with(['receipts', 'receipts.remittances'])
                ->get();

            $items = collect();

            if ($type === 'all' || $type === 'obligation') {
                foreach ($obligations as $ob) {
                    $received = $ob->receipts->sum('amount_received');
                    $remitted = $ob->receipts->flatMap->remittances->sum('amount_paid');
                    $items->push([
                        'type' => 'Obligation',
                        'title' => $ob->title,
                        'date' => $ob->due_date,
                        'amount' => $ob->amount_expected,
                        'received' => $received,
                        'remitted' => $remitted,
                        'balance' => $ob->amount_expected - $received,
                        'outstanding' => $received - $remitted,
                        'notes' => $ob->notes ?? '',
                        'status' => $ob->status,
                        'currency' => $ob->currency ?? 'GHS',
                    ]);
                }
            }

            if ($type === 'all' || $type === 'receipt') {
                foreach ($obligations as $ob) {
                    foreach ($ob->receipts as $receipt) {
                        $remitted = $receipt->remittances->sum('amount_paid');
                        $items->push([
                            'type' => 'Receipt',
                            'title' => $ob->title,
                            'date' => $receipt->date_received,
                            'amount' => $receipt->amount_received,
                            'received' => $receipt->amount_received,
                            'remitted' => $remitted,
                            'balance' => 0,
                            'outstanding' => $receipt->amount_received - $remitted,
                            'notes' => $receipt->notes ?? '',
                            'status' => $receipt->payment_method,
                            'currency' => $ob->currency ?? 'GHS',
                        ]);
                    }
                }
            }

            if ($type === 'all' || $type === 'remittance') {
                foreach ($obligations as $ob) {
                    foreach ($ob->receipts as $receipt) {
                        foreach ($receipt->remittances as $remit) {
                            $items->push([
                                'type' => 'Payment',
                                'title' => $ob->title,
                                'date' => $remit->date_paid,
                                'amount' => $remit->amount_paid,
                                'received' => 0,
                                'remitted' => $remit->amount_paid,
                                'balance' => 0,
                                'outstanding' => 0,
                                'notes' => $remit->notes ?? '',
                                'status' => $remit->payment_method,
                                'currency' => $ob->currency ?? 'GHS',
                            ]);
                        }
                    }
                }
            }

            $items = $items->sortBy('date');
        }

        $callback = function () use ($items, $symbols) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            $itemsByCurrency = $items->groupBy('currency');
            
            foreach ($itemsByCurrency as $currency => $currencyItems) {
                $symbol = $symbols[$currency] ?? '₵';
                
                fputcsv($handle, ["SmartCash - Revenue Report ($currency)"]);
                fputcsv($handle, [
                    'Date', 'Title', 'Type', 'Notes', 
                    "Amount ($currency)", "Received ($currency)", "Remitted ($currency)", 
                    "Balance ($currency)", "Outstanding ($currency)", 'Status'
                ]);
                
                foreach ($currencyItems as $item) {
                    fputcsv($handle, [
                        $item['date'],
                        $item['title'],
                        $item['type'],
                        $item['notes'],
                        $symbol . number_format($item['amount'], 2, '.', ','),
                        $symbol . number_format($item['received'], 2, '.', ','),
                        $symbol . number_format($item['remitted'], 2, '.', ','),
                        $symbol . number_format($item['balance'], 2, '.', ','),
                        $symbol . number_format($item['outstanding'], 2, '.', ','),
                        $item['status'],
                    ]);
                }
                
                fputcsv($handle, []); // Empty row between currencies
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
        $symbols = ['GHS' => '₵', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'NGN' => '₦'];

        $from = $request->input('from', now()->startOfYear()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $type = $request->input('type', 'all');

        if (! $userId) {
            $items = collect();
        } else {
            $obligations = Obligation::query()
                ->where('user_id', $userId)
                ->whereDate('due_date', '>=', $from)
                ->whereDate('due_date', '<=', $to)
                ->with(['receipts', 'receipts.remittances'])
                ->get();

            $items = collect();

            if ($type === 'all' || $type === 'obligation') {
                foreach ($obligations as $ob) {
                    $received = $ob->receipts->sum('amount_received');
                    $remitted = $ob->receipts->flatMap->remittances->sum('amount_paid');
                    $items->push([
                        'type' => 'Obligation',
                        'title' => $ob->title,
                        'date' => $ob->due_date,
                        'amount' => $ob->amount_expected,
                        'received' => $received,
                        'remitted' => $remitted,
                        'balance' => $ob->amount_expected - $received,
                        'outstanding' => $received - $remitted,
                        'notes' => $ob->notes ?? '',
                        'status' => $ob->status,
                        'currency' => $ob->currency ?? 'GHS',
                    ]);
                }
            }

            if ($type === 'all' || $type === 'receipt') {
                foreach ($obligations as $ob) {
                    foreach ($ob->receipts as $receipt) {
                        $remitted = $receipt->remittances->sum('amount_paid');
                        $items->push([
                            'type' => 'Receipt',
                            'title' => $ob->title,
                            'date' => $receipt->date_received,
                            'amount' => $receipt->amount_received,
                            'received' => $receipt->amount_received,
                            'remitted' => $remitted,
                            'balance' => 0,
                            'outstanding' => $receipt->amount_received - $remitted,
                            'notes' => $receipt->notes ?? '',
                            'status' => $receipt->payment_method,
                            'currency' => $ob->currency ?? 'GHS',
                        ]);
                    }
                }
            }

            if ($type === 'all' || $type === 'remittance') {
                foreach ($obligations as $ob) {
                    foreach ($ob->receipts as $receipt) {
                        foreach ($receipt->remittances as $remit) {
                            $items->push([
                                'type' => 'Payment',
                                'title' => $ob->title,
                                'date' => $remit->date_paid,
                                'amount' => $remit->amount_paid,
                                'received' => 0,
                                'remitted' => $remit->amount_paid,
                                'balance' => 0,
                                'outstanding' => 0,
                                'notes' => $remit->notes ?? '',
                                'status' => $remit->payment_method,
                                'currency' => $ob->currency ?? 'GHS',
                            ]);
                        }
                    }
                }
            }

            $items = $items->sortBy('date');
        }

        $itemsByCurrency = $items->groupBy('currency');
        
        $html = '<html><head><style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 40px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #f5f5f5; }
            h2 { color: #333; margin-top: 30px; }
        </style></head><body>
        <h1>SmartCash Report</h1>
        <p>Period: '.$from.' to '.$to.'</p>';

        foreach ($itemsByCurrency as $currency => $currencyItems) {
            $symbol = $symbols[$currency] ?? '₵';
            
            $html .= '<h2>'.$currency.'</h2>';
            $html .= '<table>
                <tr>
                    <th>Date</th><th>Title</th><th>Type</th><th>Notes</th>
                    <th>Amount ('.$currency.')</th><th>Received ('.$currency.')</th>
                    <th>Remitted ('.$currency.')</th><th>Balance ('.$currency.')</th>
                    <th>Outstanding ('.$currency.')</th><th>Status</th>
                </tr>';

            foreach ($currencyItems as $item) {
                $html .= '<tr>
                    <td>'.$item['date'].'</td>
                    <td>'.$item['title'].'</td>
                    <td>'.$item['type'].'</td>
                    <td>'.$item['notes'].'</td>
                    <td>'.$symbol.number_format($item['amount'], 2, '.', ',').'</td>
                    <td>'.$symbol.number_format($item['received'], 2, '.', ',').'</td>
                    <td>'.$symbol.number_format($item['remitted'], 2, '.', ',').'</td>
                    <td>'.$symbol.number_format($item['balance'], 2, '.', ',').'</td>
                    <td>'.$symbol.number_format($item['outstanding'], 2, '.', ',').'</td>
                    <td>'.$item['status'].'</td>
                </tr>';
            }

            $html .= '</table>';
        }

        $html .= '</body></html>';

        return response($html, 200, ['Content-Type' => 'text/html']);
    }
}

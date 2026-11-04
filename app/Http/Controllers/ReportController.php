<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class ReportController extends Controller
{
    // GET /admin/reports/monthly
    public function monthly(Request $request)
    {
        $report = $this->buildReport($request);

        // Kapag galing sa admin-reports.js (AJAX), laman lang ng report ang ibalik.
        // Hindi na kailangan ang header, year options, at pending counts,
        // kaya mas kaunti ang trip sa Supabase.
        if ($request->ajax()) {
            return view('admin.reports.monthly-content', $report);
        }

        $adminId = Session::get('user_id');

        // Same header/sidebar data every other admin page gets
        $headerData = app(AdminController::class)->getHeaderData($adminId);

        return view('admin.reports.monthly', array_merge($headerData, $report, [
            'yearOptions'      => $this->yearOptions(),
            'pendingCount'     => $this->pendingVerificationCount($adminId),
            'pendingLoanCount' => $this->pendingLoanCount($adminId),
        ]));
    }

    // GET /admin/reports/monthly/pdf
    public function monthlyPdf(Request $request)
    {
        $report = $this->buildReport($request);

        // Dagdag na stats para sa PDF lang (Application Overview, Loan Status, Payment Monitoring).
        // Hindi ito kasama sa screen/AJAX para hindi madagdagan ang queries ng page.
        $report['overview'] = $this->buildOverview($report['year'], $report['month'], (int) $report['summary']['approved']);

        $suffix = $report['month'] === 0
            ? $report['year'] . '-all'
            : $report['year'] . '-' . str_pad($report['month'], 2, '0', STR_PAD_LEFT);

        return Pdf::loadView('admin.reports.monthly-pdf', $report)
            ->setPaper('a4', 'portrait')
            ->download("monthly-report-{$suffix}.pdf");
    }

    // ---------------------------------------------------------------
    // Builds everything the page and the PDF need.
    // Ang data ay galing sa RPC: admin_monthly_report_data(year, month)
    // ---------------------------------------------------------------
    private function buildReport(Request $request): array
    {
        $year  = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month); // 0 = all months

        if ($year < 2000 || $year > now()->year + 1) {
            $year = now()->year;
        }
        if ($month < 0 || $month > 12) {
            $month = now()->month;
        }

        $rpcRow = DB::selectOne('SELECT admin_monthly_report_data(?, ?) as data', [$year, $month]);
        $data = json_decode($rpcRow->data);

        // 12-row breakdown for the selected year
        $rows = [];
        foreach ($data->rows as $r) {
            $rows[] = [
                'month'     => (int) $r->month,
                'label'     => date('F', mktime(0, 0, 0, (int) $r->month, 1)),
                'approved'  => (int) $r->approved,
                'collected' => (float) $r->collected,
                'interest'  => (float) $r->interest,
                'penalty'   => (float) $r->penalty,
            ];
        }

        $rowsCollection = collect($rows);

        // Summary cards: one month, or the whole year
        if ($month === 0) {
            $summary = [
                'approved'  => $rowsCollection->sum('approved'),
                'collected' => $rowsCollection->sum('collected'),
                'interest'  => $rowsCollection->sum('interest'),
                'penalty'   => $rowsCollection->sum('penalty'),
            ];
            $periodLabel = "All months of {$year}";
        } else {
            $r = $rows[$month - 1];
            $summary = [
                'approved'  => $r['approved'],
                'collected' => $r['collected'],
                'interest'  => $r['interest'],
                'penalty'   => $r['penalty'],
            ];
            $periodLabel = Carbon::create($year, $month, 1)->format('F Y');
        }

        // List of loans approved in the selected period
        $approvedList = collect($data->approved_list);

        // Kunin ang uuid ng bawat loan (isang query lang, at wala kung walang loans)
        $uuids = collect();
        if ($approvedList->isNotEmpty()) {
            $uuids = DB::table('loan_applications')
                ->whereIn('application_id', $approvedList->pluck('application_id')->all())
                ->pluck('uuid', 'application_id');
        }

        $approvedList = $approvedList->map(function ($row) use ($uuids) {
            $name = trim(($row->first_name ?? '') . ' ' . ($row->last_name ?? ''));
            $row->borrower_name = $name !== '' ? $name : ($row->fallback_name ?? '—');

            // Parehong style ng BRW- sa ibang pages: unang 8 characters ng uuid
            $uuid = $uuids[$row->application_id] ?? null;
            $row->loan_code = $uuid ? 'LN-' . strtoupper(substr($uuid, 0, 8)) : '—';
            return $row;
        });

        return [
            'year'         => $year,
            'month'        => $month,
            'periodLabel'  => $periodLabel,
            'summary'      => $summary,
            'rows'         => $rows,
            'approvedList' => $approvedList,
            'generatedAt'  => now(),
        ];
    }

    // ---------------------------------------------------------------
    // Dagdag na stats para sa PDF (Executive Summary, Application Overview,
    // Loan Status Overview, Payment Monitoring Summary).
    //
    // Period = napiling buwan, o ang buong taon kapag "All months".
    //
    //  Applications
    //   - Approved : kapareho ng bilang sa summary (galing sa RPC)
    //   - Rejected : na-reject sa period (petsa ng huling reject decision)
    //   - Pending / On Hold : isinumite (applied_at) sa period at ganoon pa rin ang status
    //
    //  Loans
    //   - Ongoing   : na-release na (proof_sent_at) hanggang katapusan ng period
    //                 at hindi pa tapos bayaran sa katapusan ng period
    //   - Completed : natapos bayaran sa period (huling paid_at)
    //
    //  Payments (installments na due sa period)
    //   - Paid      : may paid_at
    //   - Partially : walang paid_at pero may amount_paid
    //   - Overdue   : walang bayad at lampas na ang due date
    //   - Unpaid    : walang bayad at hindi pa due
    // ---------------------------------------------------------------
    private function buildOverview(int $year, int $month, int $approvedCount): array
    {
        if ($month === 0) {
            $start = Carbon::create($year, 1, 1)->startOfDay();
            $end   = Carbon::create($year, 12, 31)->endOfDay();
        } else {
            $start = Carbon::create($year, $month, 1)->startOfMonth();
            $end   = $start->copy()->endOfMonth();
        }

        // ---- Applications ----
        $rejected = DB::table('loan_applications as la')
            ->where('la.status', 'rejected')
            ->whereRaw(
                "(SELECT MAX(lap.reviewed_at) FROM loan_approvals lap
                  WHERE lap.application_id = la.application_id AND lap.decision = 'rejected') BETWEEN ? AND ?",
                [$start, $end]
            )
            ->count();

        $pending = DB::table('loan_applications')
            ->where('status', 'pending')
            ->whereBetween('applied_at', [$start, $end])
            ->count();

        $onHold = DB::table('loan_applications')
            ->where('status', 'on_hold')
            ->whereBetween('applied_at', [$start, $end])
            ->count();

        // ---- Loans (released lang, kasama ang petsa ng huling bayad) ----
        $released = DB::table('loan_applications as la')
            ->leftJoin(
                DB::raw('(SELECT application_id, MAX(paid_at) AS completed_at
                          FROM loan_payment_schedules GROUP BY application_id) AS s'),
                's.application_id', '=', 'la.application_id'
            )
            ->whereNotNull('la.proof_sent_at')
            ->whereIn('la.status', ['active', 'approved', 'completed', 'defaulted'])
            ->select('la.status', 'la.proof_sent_at', 's.completed_at')
            ->get();

        $ongoing = 0;
        $completed = 0;
        foreach ($released as $loan) {
            $releasedAt  = Carbon::parse($loan->proof_sent_at);
            $completedAt = ($loan->status === 'completed' && $loan->completed_at)
                ? Carbon::parse($loan->completed_at)
                : null;

            if ($completedAt && $completedAt->between($start, $end)) {
                $completed++;
            }

            $finishedByEnd = $completedAt && $completedAt->lte($end);
            if ($releasedAt->lte($end) && !$finishedByEnd) {
                $ongoing++;
            }
        }

        // ---- Payments ----
        $today = now()->toDateString();

        $pay = DB::table('loan_payment_schedules')
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN paid_at IS NOT NULL THEN 1 ELSE 0 END), 0) AS paid,
                 COALESCE(SUM(CASE WHEN paid_at IS NULL AND amount_paid > 0 THEN 1 ELSE 0 END), 0) AS partial,
                 COALESCE(SUM(CASE WHEN paid_at IS NULL AND amount_paid = 0 AND due_date < ? THEN 1 ELSE 0 END), 0) AS overdue,
                 COALESCE(SUM(CASE WHEN paid_at IS NULL AND amount_paid = 0 AND due_date >= ? THEN 1 ELSE 0 END), 0) AS unpaid",
                [$today, $today]
            )
            ->first();

        return [
            'applications' => [
                'approved' => $approvedCount,
                'rejected' => $rejected,
                'pending'  => $pending,
                'on_hold'  => $onHold,
            ],
            'loans' => [
                'ongoing'   => $ongoing,
                'completed' => $completed,
            ],
            'payments' => [
                'paid'    => (int) $pay->paid,
                'partial' => (int) $pay->partial,
                'unpaid'  => (int) $pay->unpaid,
                'overdue' => (int) $pay->overdue,
            ],
        ];
    }

    // Years from the first application up to now (newest first)
    private function yearOptions(): array
    {
        $first = DB::table('loan_applications')->min('applied_at');
        $startYear = $first ? Carbon::parse($first)->year : now()->year;

        return range(now()->year, min($startYear, now()->year));
    }

    // Same queries AdminController uses for the sidebar badges
    private function pendingVerificationCount($adminId)
    {
        return DB::table('borrowerpersonaldetails as bpd')
            ->where('bpd.status', 'pending')
            ->whereNotExists(function ($q) use ($adminId) {
                $q->select(DB::raw(1))
                    ->from('detailsapproval as da')
                    ->whereColumn('da.detail_id', 'bpd.detail_id')
                    ->where('da.admin_id', $adminId);
            })
            ->count();
    }

    private function pendingLoanCount($adminId)
    {
        return DB::table('loan_applications as la')
            ->where('la.status', 'pending')
            ->whereNotExists(function ($q) use ($adminId) {
                $q->select(DB::raw(1))
                    ->from('loan_approvals as lap')
                    ->whereColumn('lap.application_id', 'la.application_id')
                    ->where('lap.admin_id', $adminId);
            })
            ->count();
    }
}
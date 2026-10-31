<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;


class PaymentMethodController extends Controller
{
    // ------------------------------------------------------------
    // Admin > Loans > Payment Methods
    // ------------------------------------------------------------

    public function index()
    {
        $adminId = Session::get('user_id');

        $methods = DB::table('payment_methods as pm')
            ->leftJoin('loan_applications as la', 'la.payment_method_id', '=', 'pm.id')
            ->groupBy('pm.id')
            ->select('pm.*', DB::raw('COUNT(la.application_id) as loans_count'))
            ->orderByDesc('pm.is_active')
            ->orderBy('pm.provider')
            ->orderBy('pm.account_label')
            ->get();

        return view('admin.payment-methods', [
            'methods'          => $methods,
            'pendingCount'     => 0,
            'pendingLoanCount' => $this->getPendingLoanCount($adminId),
        ]);
    }

    public function destroy($id)
    {
        $method = DB::table('payment_methods')->where('id', $id)->first();
        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Payment method not found.'], 404);
        }

        // Active accounts must be deactivated first.
        if ($method->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Deactivate this account first before deleting it.',
            ], 422);
        }

        try {
            $unassigned = DB::transaction(function () use ($id) {
                // Loans using it go back to "no payment account assigned".
                $count = DB::table('loan_applications')
                    ->where('payment_method_id', $id)
                    ->update(['payment_method_id' => null]);

                DB::table('payment_methods')->where('id', $id)->delete();

                return $count;
            });
        } catch (\Throwable $e) {
            Log::error('payment method delete failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Could not delete this payment method. Please try again.',
            ], 500);
        }

        AuditLogger::log('payment_method.deleted', [
            'entity_type' => 'payment_method',
            'entity_id'   => $id,
            'description' => 'Deleted payment account ' . $this->label($method),
            'old'         => $this->snapshot($method),
            'context'     => ['unassigned_loans' => (int) $unassigned],
        ]);

        return response()->json([
            'success' => true,
            'message' => $unassigned > 0
                ? "Payment method deleted. {$unassigned} loan(s) now have no payment account assigned."
                : 'Payment method deleted.',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $newId = DB::table('payment_methods')->insertGetId($data + [
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'id');

        Log::info('Payment method added by admin ' . Session::get('user_id') . ': ' . $data['provider'] . ' / ' . $data['account_label']);

        AuditLogger::log('payment_method.created', [
            'entity_type' => 'payment_method',
            'entity_id'   => $newId,
            'description' => 'Added payment account ' . $this->label($data),
            'new'         => $this->snapshot($data),
        ]);

        return response()->json(['success' => true, 'message' => 'Payment method added.']);
    }

    public function update(Request $request, $id)
    {
        $method = DB::table('payment_methods')->where('id', $id)->first();

        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Payment method not found.'], 404);
        }

        $data = $this->validated($request, (int) $id);

        // Editing changes what borrowers will see from now on. Receipts that were
        // already uploaded keep their own snapshot (payment_receipts.payment_method_snapshot).
        DB::table('payment_methods')->where('id', $id)->update($data + ['updated_at' => now()]);

        Log::info('Payment method #' . $id . ' edited by admin ' . Session::get('user_id'));

        // Log only the fields that really changed
        [$old, $new] = AuditLogger::diff($this->snapshot($method), $this->snapshot($data));
        if ($new) {
            AuditLogger::log('payment_method.updated', [
                'entity_type' => 'payment_method',
                'entity_id'   => $id,
                'description' => 'Edited payment account ' . $this->label($method)
                    . ' (changed: ' . implode(', ', array_map(fn ($k) => str_replace('_', ' ', $k), array_keys($new))) . ')',
                'old'         => $old,
                'new'         => $new,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Payment method updated.']);
    }

    public function toggle(Request $request, $id)
    {
        $method = DB::table('payment_methods')->where('id', $id)->first();

        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Payment method not found.'], 404);
        }

        $newState = !$method->is_active;

        DB::table('payment_methods')->where('id', $id)->update([
            'is_active'  => $newState,
            'updated_at' => now(),
        ]);

        $loans = (int) DB::table('loan_applications')->where('payment_method_id', $id)->count();

        $message = $newState
            ? 'Payment method activated. It can be assigned to loans again.'
            : 'Payment method deactivated. It can no longer be newly assigned'
                . ($loans > 0 ? ", but the {$loans} loan(s) already using it will keep showing it." : '.');

        Log::info('Payment method #' . $id . ($newState ? ' activated' : ' deactivated') . ' by admin ' . Session::get('user_id'));

        AuditLogger::log($newState ? 'payment_method.activated' : 'payment_method.deactivated', [
            'entity_type' => 'payment_method',
            'entity_id'   => $id,
            'description' => ($newState ? 'Activated' : 'Deactivated') . ' payment account ' . $this->label($method),
            'old'         => ['is_active' => (bool) $method->is_active],
            'new'         => ['is_active' => $newState],
            'context'     => ['loans_using_it' => $loans],
        ]);

        return response()->json(['success' => true, 'is_active' => $newState, 'message' => $message]);
    }

    // ------------------------------------------------------------
    // Admin > Ongoing Loan Detail > Assigned Payment Method
    // ------------------------------------------------------------

    public function assign(Request $request, $uuid)
    {
        $request->validate([
            'payment_method_id' => ['required', 'integer'],
        ], [
            'payment_method_id.required' => 'Please select a payment method.',
        ]);

        $loan = DB::table('loan_applications')->where('uuid', $uuid)->first();

        if (!$loan) {
            return response()->json(['success' => false, 'message' => 'Loan not found.'], 404);
        }

        if (!in_array($loan->status, ['approved', 'active'], true)) {
            return response()->json(['success' => false, 'message' => 'A payment method can only be assigned to an ongoing loan.'], 422);
        }

        $method = DB::table('payment_methods')->where('id', (int) $request->input('payment_method_id'))->first();

        if (!$method) {
            return response()->json(['success' => false, 'message' => 'Payment method not found.'], 422);
        }

        $isSame = (int) $loan->payment_method_id === (int) $method->id;

        // Only ACTIVE methods can be newly assigned. Re-saving the one that is
        // already on the loan is harmless, so it is not blocked.
        if (!$method->is_active && !$isSame) {
            return response()->json(['success' => false, 'message' => 'This payment method is inactive and cannot be assigned.'], 422);
        }

        if (!$isSame) {
            // What the loan used before (for the audit log)
            $previous = $loan->payment_method_id
                ? DB::table('payment_methods')->where('id', $loan->payment_method_id)->first()
                : null;

            DB::table('loan_applications')
                ->where('application_id', $loan->application_id)
                ->update(['payment_method_id' => $method->id]);

            Log::info("Payment method #{$method->id} assigned to loan {$loan->application_id} by admin " . Session::get('user_id'));

            $loanCode = 'LN-' . strtoupper(substr((string) $uuid, 0, 8));
            $hadBefore = !empty($loan->payment_method_id);

            AuditLogger::log('payment_method.assigned', [
                'entity_type' => 'loan',
                'entity_id'   => $loan->application_id,
                'description' => $hadBefore
                    ? "Changed the payment account of loan {$loanCode} from "
                        . ($previous ? $this->label($previous) : 'a removed account') . ' to ' . $this->label($method)
                    : "Assigned payment account {$this->label($method)} to loan {$loanCode}",
                'old'         => ['payment_account' => $hadBefore ? ($previous ? $this->label($previous) : 'Removed account') : null],
                'new'         => ['payment_account' => $this->label($method)],
                'context'     => [
                    'loan_uuid'             => $uuid,
                    'old_payment_method_id' => $loan->payment_method_id,
                    'new_payment_method_id' => $method->id,
                ],
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $isSame ? 'This payment method is already assigned.' : 'Payment method assigned.',
            'method'  => [
                'id'             => $method->id,
                'provider'       => $method->provider,
                'account_label'  => $method->account_label,
                'account_name'   => $method->account_name,
                'account_number' => $method->account_number,
                'instructions'   => $method->instructions,
                'is_active'      => (bool) $method->is_active,
            ],
        ]);
    }

    // ------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $request->merge([
            'provider'       => trim((string) $request->input('provider')),
            'account_label'  => trim((string) $request->input('account_label')),
            'account_name'   => trim((string) $request->input('account_name')),
            'account_number' => trim((string) $request->input('account_number')),
            'instructions'   => trim((string) $request->input('instructions')),
        ]);

        $validated = $request->validate([
            'provider'       => ['required', 'string', 'max:60'],
            'account_label'  => ['required', 'string', 'max:100'],
            'account_name'   => ['required', 'string', 'max:120'],
            'account_number' => [
                'required', 'string', 'max:60', 'regex:/^[0-9][0-9\s\-]{3,39}$/',
                Rule::unique('payment_methods', 'account_number')
                    ->where('provider', $request->input('provider'))
                    ->ignore($ignoreId),
            ],
            'instructions'   => ['nullable', 'string', 'max:1000'],
        ], [
            'provider.required'       => 'Please enter the provider (e.g. GCash, BPI, BDO).',
            'account_label.required'  => 'Please enter an account label.',
            'account_name.required'   => 'Please enter the account name.',
            'account_number.required' => 'Please enter the account number.',
            'account_number.regex'    => 'The account number may only contain digits, spaces and dashes.',
            'account_number.unique'   => 'This account number is already saved for that provider.',
        ]);

        $validated['instructions'] = $validated['instructions'] !== '' ? $validated['instructions'] : null;

        return $validated;
    }

    /** "GCash — Juan Dela Cruz" (works with a DB row or an array) */
    private function label($row): string
    {
        $a = (array) $row;
        $parts = array_filter([trim((string) ($a['provider'] ?? '')), trim((string) ($a['account_name'] ?? ''))], fn ($p) => $p !== '');
        return implode(' — ', $parts);
    }

    /** The fields worth showing in the audit log. */
    private function snapshot($row): array
    {
        $a = (array) $row;
        return [
            'provider'       => $a['provider'] ?? null,
            'account_name'   => $a['account_name'] ?? null,
            'account_number' => $a['account_number'] ?? null,
        ];
    }

    // Same rule as AdminController::getPendingLoanCount(), so the sidebar badge stays correct on this page.
    private function getPendingLoanCount($adminId)
    {
        return DB::table('loan_applications as la')
            ->where('la.status', 'pending')
            ->whereNotExists(function ($query) use ($adminId) {
                $query->select(DB::raw(1))
                    ->from('loan_approvals as lap')
                    ->whereColumn('lap.application_id', 'la.application_id')
                    ->where('lap.admin_id', $adminId);
            })
            ->count();
    }
}
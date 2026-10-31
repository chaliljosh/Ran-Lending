<?php

// app/Http/Controllers/AuditLogController.php
// Read-only pages:
//   GET /admin/audit-logs           -> redirects to the Admin page (old links / ?tab=borrower still work)
//   GET /admin/audit-logs/admin     -> Admin audit logs
//   GET /admin/audit-logs/borrower  -> Borrower audit logs

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    private const PER_PAGE = 25;

    /** Page key => label, actor roles, and route name of that page */
    private const SCOPES = [
        'admin' => [
            'label' => 'Admin',
            'roles' => ['admin', 'system'],
            'route' => 'admin.audit-logs.admin',
            'actor_filter' => true,
        ],
        'borrower' => [
            'label' => 'Borrower',
            'roles' => ['borrower', 'guest'],
            'route' => 'admin.audit-logs.borrower',
            'actor_filter' => false, // no person dropdown on the Borrower page
        ],
    ];

    /** Filter dropdown: key => [label, action patterns] */
    private const GROUPS = [
        'auth'           => ['Authentication', ['auth.%']],
        'loan'           => ['Loans & decisions', ['loan.%', 'borrower.%']],
        'money'          => ['Payments & penalties', ['payment.%', 'receipt.%', 'void.%', 'penalty.%']],
        'payment_method' => ['Payment methods', ['payment_method.%']],
        'account'        => ['Accounts', ['account.%']],
        'request'        => ['Other requests (auto)', ['request.%']],
    ];

    /** Action prefix => group key (for the colored badge) */
    private const PREFIX_GROUP = [
        'auth' => 'auth',
        'loan' => 'loan', 'borrower' => 'loan',
        'payment' => 'money', 'receipt' => 'money', 'void' => 'money', 'penalty' => 'money',
        'payment_method' => 'payment_method',
        'account' => 'account',
        'request' => 'request',
    ];

    /** /admin/audit-logs — keeps old links working (including ?tab=borrower). */
    public function index(Request $request)
    {
        $scope = $request->query('tab') === 'borrower' ? 'borrower' : 'admin';

        return redirect()->route(self::SCOPES[$scope]['route'], $request->except('tab'));
    }

    public function admin(Request $request)
    {
        return $this->show($request, 'admin');
    }

    public function borrower(Request $request)
    {
        return $this->show($request, 'borrower');
    }

    /** Shared page logic for both scopes. */
    private function show(Request $request, string $scope)
    {
        $config = self::SCOPES[$scope];

        $data = [
            'scope'        => $scope,
            'scopeLabel'   => $config['label'],
            'pageRoute'    => $config['route'],
            'hasActorFilter' => $config['actor_filter'],
            'groups'       => array_map(fn ($g) => $g[0], self::GROUPS),
            'tableMissing' => !Schema::hasTable('audit_logs'),
            'logs'         => null,
            'actors'       => collect(),
            'filters'      => [],
        ];

        if ($data['tableMissing']) {
            return view('admin.audit-logs', $data);
        }

        $data['filters'] = $this->filtersFrom($request);

        if (!$config['actor_filter']) {
            $data['filters']['actor'] = '';
        }

        $data['logs'] = $this->applyFilters(
                DB::table('audit_logs')->whereIn('actor_role', $config['roles']),
                $data['filters']
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString()
            ->through(fn ($row) => $this->present($row));

        $data['actors'] = $config['actor_filter'] ? $this->actorsFor($config['roles']) : collect();

        return view('admin.audit-logs', $data);
    }

    /** Read the filter values from the URL. */
    private function filtersFrom(Request $request): array
    {
        return [
            'q'     => trim((string) $request->query('q', '')),
            'group' => (string) $request->query('group', ''),
            'actor' => (string) $request->query('actor', ''),
            'from'  => (string) $request->query('from', ''),
            'to'    => (string) $request->query('to', ''),
        ];
    }

    /** Add the search / type / actor / date filters to a query. */
    private function applyFilters(Builder $query, array $filters): Builder
    {
        if ($filters['q'] !== '') {
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $query->where(function ($w) use ($like) {
                $w->where('description', 'ilike', $like)
                    ->orWhere('actor_name', 'ilike', $like)
                    ->orWhere('action', 'ilike', $like)
                    ->orWhere('entity_id', 'ilike', $like)
                    ->orWhere('ip_address', 'ilike', $like);
            });
        }

        if (isset(self::GROUPS[$filters['group']])) {
            $patterns = self::GROUPS[$filters['group']][1];
            $query->where(function ($w) use ($patterns) {
                foreach ($patterns as $pattern) {
                    $w->orWhere('action', 'like', $pattern);
                }
            });
        }

        if ($filters['actor'] !== '' && ctype_digit($filters['actor'])) {
            $query->where('actor_user_id', (int) $filters['actor']);
        }

        foreach (['from' => ['>=', 'startOfDay'], 'to' => ['<=', 'endOfDay']] as $key => [$operator, $edge]) {
            if ($filters[$key] === '') {
                continue;
            }
            try {
                $query->where('created_at', $operator, Carbon::parse($filters[$key], config('app.timezone'))->{$edge}());
            } catch (\Throwable $e) {
                // ignore an invalid date
            }
        }

        return $query;
    }

    /** People who appear in this page's logs (for the Admin / Borrower dropdown). */
    private function actorsFor(array $roles)
    {
        return DB::table('audit_logs')
            ->whereIn('actor_role', $roles)
            ->whereNotNull('actor_user_id')
            ->select('actor_user_id', DB::raw('max(actor_name) as actor_name'))
            ->groupBy('actor_user_id')
            ->orderBy('actor_name')
            ->limit(200)
            ->get();
    }

    /** Turn a DB row into what the page needs. */
    private function present($row)
    {
        $when = Carbon::parse($row->created_at)->timezone(config('app.timezone'));
        $prefix = explode('.', (string) $row->action)[0];
        $actor = $row->actor_name ?: ($row->actor_role === 'guest' ? 'Guest' : 'System');

        $target = null;
        if (!empty($row->entity_type)) {
            $target = str_replace('_', ' ', $row->entity_type) . (!empty($row->entity_id) ? ' #' . $row->entity_id : '');
        }

        $decode = fn ($value) => is_string($value) ? json_decode($value, true) : $value;

        return (object) [
            'id'          => $row->id,
            'when'        => $when->format('M d, Y'),
            'time'        => $when->format('g:i A'),
            'actor_name'  => $actor,
            'actor_role'  => $row->actor_role ?: 'system',
            'action'      => $row->action,
            'group'       => self::PREFIX_GROUP[$prefix] ?? 'other',
            'target'      => $target,
            'description' => $row->description,
            'ip'          => $row->ip_address,
            'payload'     => [
                'id'          => $row->id,
                'when'        => $when->format('M d, Y g:i:s A'),
                'actor'       => $actor,
                'role'        => $row->actor_role,
                'action'      => $row->action,
                'target'      => $target,
                'description' => $row->description,
                'ip'          => $row->ip_address,
                'ua'          => $row->user_agent,
                'old'         => $decode($row->old_values),
                'new'         => $decode($row->new_values),
                'context'     => $decode($row->context),
            ],
        ];
    }
}
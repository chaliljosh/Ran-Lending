<?php

// app/Services/AuditLogger.php
//
// Central helper for the Global Audit Logs.
//
//   AuditLogger::log('payment_method.created', [
//       'entity_type' => 'payment_method',
//       'entity_id'   => $id,
//       'description' => 'Added GCash account of Juan Dela Cruz',
//       'new'         => ['provider' => 'GCash', 'account_name' => 'Juan Dela Cruz'],
//   ]);
//
// Rules:
//  - Logging NEVER breaks the real action (everything is wrapped in try/catch).
//  - Call it AFTER the action succeeded (after the transaction commits).
//  - Passwords, OTPs and tokens are never stored; ID numbers are masked.

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AuditLogger
{
    /** Keys that are never stored (any depth). */
    private const SECRET_KEYS = [
        'password', 'password_confirmation', 'current_password', 'new_password',
        'otp', 'otp_code', 'code', 'token', '_token', 'remember_token', 'api_token',
    ];

    /** Keys that are masked (only the last 3 characters are kept). */
    private const MASK_KEYS = [
        'id_number', 'id_no', 'valid_id_number', 'government_id_number', 'gov_id_number',
    ];

    /**
     * Write one audit row.
     *
     * $opts keys (all optional):
     *   entity_type, entity_id, description, old (array), new (array), context (array),
     *   actor => ['id' => ?, 'role' => ?, 'name' => ?]   // override, e.g. failed login
     */
    public static function log(string $action, array $opts = []): void
    {
        try {
            $request = request();
            $actor = $opts['actor'] ?? self::resolveActor();

            DB::table('audit_logs')->insert([
                'actor_user_id' => $actor['id'] ?? null,
                'actor_role'    => $actor['role'] ?? null,
                'actor_name'    => isset($actor['name']) ? mb_substr((string) $actor['name'], 0, 150) : null,
                'action'        => mb_substr($action, 0, 80),
                'entity_type'   => isset($opts['entity_type']) ? mb_substr((string) $opts['entity_type'], 0, 60) : null,
                'entity_id'     => isset($opts['entity_id']) ? mb_substr((string) $opts['entity_id'], 0, 64) : null,
                'description'   => $opts['description'] ?? null,
                'old_values'    => self::json($opts['old'] ?? null),
                'new_values'    => self::json($opts['new'] ?? null),
                'ip_address'    => $request ? mb_substr((string) $request->ip(), 0, 45) : null,
                'user_agent'    => $request ? (mb_substr((string) $request->userAgent(), 0, 255) ?: null) : null,
                'context'       => self::json($opts['context'] ?? null),
            ]);

            // Tells the AuditRequests middleware this request is already logged (no duplicate).
            if ($request) {
                $request->attributes->set('audit_logged', true);
            }
        } catch (\Throwable $e) {
            Log::error('Audit log failed: ' . $e->getMessage(), ['action' => $action]);
        }
    }

    /**
     * Who is doing this? Kinukuha sa session (inilalagay ng AuthController::login).
     * Returns ['id' => ?, 'role' => ?, 'name' => ?]
     */
    public static function resolveActor(): array
    {
        $request = request();
        $session = ($request && $request->hasSession()) ? $request->session() : null;

        $id   = $session ? $session->get('user_id') : null;
        $role = $session ? strtolower((string) $session->get('user_role')) : '';
        $name = $session ? ($session->get('user_name') ?: $session->get('user_email')) : null;

        // Kapag walang malinaw na role: may id = system, wala = guest (hindi pa naka-login)
        if (!in_array($role, ['admin', 'borrower'], true)) {
            $role = $id ? 'system' : 'guest';
        }

        return ['id' => $id, 'role' => $role, 'name' => $name ?: null];
    }

    /** Keep only the fields that really changed. Returns [oldChanged, newChanged]. */
    public static function diff(array $old, array $new): array
    {
        $oldOut = [];
        $newOut = [];
        foreach (array_unique(array_merge(array_keys($old), array_keys($new))) as $key) {
            $a = $old[$key] ?? null;
            $b = $new[$key] ?? null;

            $same = (is_scalar($a) || $a === null) && (is_scalar($b) || $b === null)
                ? (string) $a === (string) $b
                : json_encode($a) === json_encode($b);

            if (!$same) {
                $oldOut[$key] = $a;
                $newOut[$key] = $b;
            }
        }
        return [$oldOut, $newOut];
    }

    /** "1234567" => "••••567" */
    public static function mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
        $len = mb_strlen($value);
        return $len <= 3
            ? str_repeat('•', $len)
            : str_repeat('•', $len - 3) . mb_substr($value, -3);
    }

    // ---------- internals ----------

    private static function json($data): ?string
    {
        if ($data === null || $data === [] || $data === '') {
            return null;
        }
        return json_encode(self::clean($data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
    }

    private static function clean($data)
    {
        if ($data instanceof UploadedFile) {
            return '[file]';
        }
        if (is_object($data)) {
            $data = method_exists($data, 'toArray') ? $data->toArray() : (array) $data;
        }
        if (is_array($data)) {
            $out = [];
            foreach ($data as $key => $value) {
                $lower = strtolower((string) $key);
                if (in_array($lower, self::SECRET_KEYS, true)) {
                    $out[$key] = '[hidden]';
                } elseif (in_array($lower, self::MASK_KEYS, true)) {
                    $out[$key] = self::mask(is_scalar($value) ? (string) $value : null);
                } else {
                    $out[$key] = self::clean($value);
                }
            }
            return $out;
        }
        if (is_string($data) && mb_strlen($data) > 500) {
            return mb_substr($data, 0, 500) . '…';
        }
        return $data;
    }
}
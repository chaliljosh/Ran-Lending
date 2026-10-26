<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PhDevUtils\Postal\PostalCodes;

class ZipcodeController extends Controller
{
    /**
     * Hanggang ganito karaming zipcode lang ang ibinabalik bilang "mga posibleng zipcode".
     * Kapag mas marami rito, masyado nang malabo para maging suhestiyon.
     */
    private const MAX_SUGGESTIONS = 8;

    /**
     * GET /api/zipcode?city=<PSGC city code>
     *
     * Dalawang format ng PSGC code ang tinatanggap:
     *  - 10 digits (bago, galing sa psgc.cloud): hal. 0305421000
     *      region(2) + province(3) + city(2) + barangay(3)
     *  - 9 digits (luma): hal. 035421000
     *      region(2) + province(2) + city(2) + barangay(3)
     *
     * Ang phdevutils/postal ay gumagamit ng 6-digit na luma (035421), kaya kino-convert
     * ang 10-digit: region (2) + huling 2 digit ng province + city (2).
     *
     * Response:
     *  - zip:  ang zipcode kung iisa lang ang zipcode ng city/municipality, kung hindi, null
     *  - zips: mga posibleng zipcode (walang laman kung wala o sobrang dami)
     *
     * NCR: sa dataset ng package, lahat ng zipcode ng Metro Manila ay naka-roll up sa
     * iisang code (133900), hindi per city. Kaya hindi ito ginagamit para sa NCR
     * (PSGC codes na nagsisimula sa 13): manual ang zipcode doon.
     */
    public function show(Request $request): JsonResponse
    {
        $digits = preg_replace('/\D/', '', (string) $request->query('city', ''));

        if (strlen($digits) < 9) {
            return response()->json(['success' => false, 'zip' => null, 'zips' => []], 422);
        }

        // NCR
        if (str_starts_with($digits, '13')) {
            return response()->json(['success' => true, 'zip' => null, 'zips' => []]);
        }

        if (strlen($digits) >= 10) {
            // Bagong 10-digit format -> luma 6-digit
            $cityMunCode = substr($digits, 0, 2) . substr($digits, 3, 2) . substr($digits, 5, 2);
        } else {
            // Lumang 9-digit format
            $cityMunCode = substr($digits, 0, 6);
        }

        try {
            $rows = PostalCodes::list(['cityMunCode' => $cityMunCode]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['success' => false, 'zip' => null, 'zips' => []], 500);
        }

        $zips = collect($rows)
            ->pluck('zip')
            ->filter()
            ->map(fn ($z) => (string) $z)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'zip'     => count($zips) === 1 ? $zips[0] : null,
            'zips'    => count($zips) <= self::MAX_SUGGESTIONS ? $zips : [],
        ]);
    }
}
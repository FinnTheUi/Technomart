<?php

namespace App\Http\Controllers;

use App\Enums\StallStatus;
use App\Models\Stall;
use Illuminate\Http\JsonResponse;

/**
 * Public live-availability feed polled by every stall map.
 */
class StallAvailabilityController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $stalls = Stall::whereNotNull('stall_number')->orderBy('floor_number')->orderBy('stall_number')->get();

        return response()->json([
            'updated_at' => now()->toIso8601String(),
            'counts' => collect(StallStatus::cases())->mapWithKeys(
                fn (StallStatus $status) => [$status->value => $stalls->where('status', $status)->count()]
            ),
            'stalls' => $stalls->map->toPublicArray()->values(),
        ])->header('Cache-Control', 'no-store');
    }
}

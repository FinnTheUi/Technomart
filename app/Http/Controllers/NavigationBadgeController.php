<?php

namespace App\Http\Controllers;

use App\Services\NavigationBadgeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavigationBadgeController extends Controller
{
    public function __invoke(Request $request, NavigationBadgeService $badges): JsonResponse
    {
        return response()->json($badges->counts($request->user()));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Stall;
use Illuminate\View\View;

class HomeController extends Controller
{
    /** Landing page with the public floor plan. */
    public function __invoke(): View
    {
        $stalls = Stall::orderBy('stall_number')->get();

        return view('welcome', [
            'floor1Stalls' => $stalls->where('floor_number', 1)->keyBy('stall_number'),
            'floor2Stalls' => $stalls->where('floor_number', 2)->keyBy('stall_number'),
        ]);
    }
}

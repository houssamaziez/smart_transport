<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DriverEarning;
use Carbon\Carbon;

class EarningController extends Controller
{

    public function index(Request $request)
    {
        $driverId = $request->user()->id;

        $earnings = DriverEarning::with('order')
            ->where('driver_id', $driverId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status'   => true,
            'earnings' => $earnings
        ]);
    }

    /**
     * ✅ عرض تفاصيل ربح واحد
     */
    public function show($id, Request $request)
    {
        $earning = DriverEarning::with('order')
            ->where('driver_id', $request->user()->id)
            ->find($id);

        if (!$earning) {
            return response()->json([
                'status'  => false,
                'message' => 'Earning not found'
            ], 404);
        }

        return response()->json([
            'status'  => true,
            'earning' => $earning
        ]);
    }

    /**
     * ✅ ملخص الأرباح (اليوم / الأسبوع / الشهر)
     */
    public function summary(Request $request)
    {
        $driverId = $request->user()->id;

        $today = DriverEarning::where('driver_id', $driverId)
            ->whereDate('created_at', Carbon::today())
            ->sum('amount');

        $week = DriverEarning::where('driver_id', $driverId)
            ->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->sum('amount');

        $month = DriverEarning::where('driver_id', $driverId)
            ->whereMonth('created_at', Carbon::now()->month)
            ->sum('amount');

        return response()->json([
            'status' => true,
            'summary' => [
                'today' => $today,
                'week'  => $week,
                'month' => $month,
            ]
        ]);
    }
}

<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Booking;

/**
 * @OA\Get(
 *     path="/api/mua/reports",
 *     summary="Lihat laporan pendapatan dan statistik booking MUA",
 *     tags={"Report"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(name="year", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Parameter(name="month", in="query", required=false, @OA\Schema(type="integer")),
 *     @OA\Response(response=200, description="Laporan berhasil dikembalikan")
 * )
 */

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->query('year', now()->year);
        $month = $request->query('month');

        $query = Booking::where('mua_id', Auth::id())
            ->where('payment_status', 'paid')
            ->whereYear('date', $year);

        if ($month) {
            $query->whereMonth('date', $month);
        }

        $bookings = $query->with('service')->get();

        $totalIncome = $bookings->sum('total_price');
        $totalBookings = $bookings->count();

        $serviceStats = $bookings->groupBy('service_id')->map(function ($group) {
            return [
                'name' => optional($group->first()->service)->name,
                'count' => $group->count(),
                'income' => $group->sum('total_price'),
            ];
        })->values();

        return response()->json([
            'year' => $year,
            'month' => $month,
            'total_income' => $totalIncome,
            'total_bookings' => $totalBookings,
            'services' => $serviceStats,
        ]);
    }
}

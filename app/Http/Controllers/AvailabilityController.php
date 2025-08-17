<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\User;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function show(Request $request, $id)
    {
        $date = $request->query('date');
        if (!$date) {
            return response()->json(['error' => 'Date parameter is required'], 400);
        }

        $mua = User::where('id', $id)->where('role', 'mua')->firstOrFail();

        $defaultSlots = collect(range(8, 17))->map(function ($hour) {
            return str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
        });

        $bookedTimes = Booking::where('mua_id', $mua->id)
            ->where('date', $date)
            ->pluck('time')
            ->map(fn($t) => substr($t, 0, 5));

        $availableSlots = $defaultSlots->filter(fn($slot) => !$bookedTimes->contains($slot))->values();

        return response()->json([
            'date' => $date,
            'available_slots' => $availableSlots
        ]);
    }
}

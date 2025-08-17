<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use App\Models\BlockedTimeSlot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BlockedTimeSlotController extends Controller
{
    /**
     * Get all blocked time slots for the authenticated MUA
     */
    public function index(Request $request)
    {
        
        $query = BlockedTimeSlot::where('mua_id', Auth::id());
        
        $blockedSlots = $query->get()->map(function ($slot) {
            return [
                'id' => $slot->id,
                'date' => $slot->date,
                'reason' => $slot->reason,
                'is_full_day' => $slot->is_full_day
            ];
        });
        
        return response()->json($blockedSlots);
    }

    /**
     * Get blocked time slots for a specific MUA (public endpoint)
     */
    public function getBlockedSlots($muaId, Request $request)
    {
        
        $query = BlockedTimeSlot::where('mua_id', $muaId);
        
        $blockedSlots = $query->get()->map(function ($slot) {
            return [
                'date' => $slot->date,
                'reason' => $slot->reason,
                'is_full_day' => $slot->is_full_day
            ];
        });
        
        return response()->json([
            'success' => true,
            'data' => $blockedSlots
        ]);
    }

    /**
     * Create a new blocked time slot
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'reason' => 'string|max:255',
            'is_full_day' => 'boolean'
        ]);

        // Check if the date is already blocked for this MUA
        $existingBlock = BlockedTimeSlot::where('mua_id', Auth::id())
            ->where('date', $request->date)
            ->first();

        if ($existingBlock) {
            return response()->json([
                'success' => false,
                'message' => 'This date is already blocked for your profile',
                'data' => $existingBlock
            ], 422);
        }

        $blockedSlot = BlockedTimeSlot::create([
            'mua_id' => Auth::id(),
            'date' => $request->date,
            'reason' => $request->reason,
            'is_full_day' => $request->is_full_day ?? false
        ]);

        return response()->json([
            'success' => true,
            'data' => $blockedSlot,
            'message' => 'Blocked time slot created successfully'
        ]);
    }

    /**
     * Update a blocked time slot
     */
    public function update(Request $request, $id)
    {
        $blockedSlot = BlockedTimeSlot::where('mua_id', Auth::id())->findOrFail($id);
        
        $request->validate([
            'date' => 'required|date',
            'reason' => 'string|max:255',
            'is_full_day' => 'boolean'
        ]);

        $blockedSlot->update([
            'date' => $request->date,
            'reason' => $request->reason,
            'is_full_day' => $request->is_full_day ?? false
        ]);

        return response()->json([
            'success' => true,
            'data' => $blockedSlot,
            'message' => 'Blocked time slot updated successfully'
        ]);
    }

    /**
     * Delete a blocked time slot
     */
    public function destroy($id)
    {
        $blockedSlot = BlockedTimeSlot::where('mua_id', Auth::id())->findOrFail($id);
        $blockedSlot->delete();

        return response()->json([
            'success' => true,
            'message' => 'Blocked time slot deleted successfully'
        ]);
    }
}

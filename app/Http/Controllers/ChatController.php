<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Chat;
use App\Models\Booking;
use App\Helpers\NotifyHelper;

class ChatController extends Controller
{
    public function index($booking_id)
    {
        $booking = Booking::where('id', $booking_id)
            ->where(function ($q) {
                $q->where('customer_id', Auth::id())
                  ->orWhere('mua_id', Auth::id());
            })
            ->firstOrFail();

        $messages = Chat::where('booking_id', $booking_id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return response()->json($messages);
    }

    public function store(Request $request, $booking_id)
    {
        $booking = Booking::where('id', $booking_id)
            ->where('status', 'confirmed')
            ->where(function ($q) {
                $q->where('customer_id', Auth::id())
                  ->orWhere('mua_id', Auth::id());
            })
            ->firstOrFail();

        $request->validate([
            'message' => 'required|string'
        ]);

        $chat = Chat::create([
            'booking_id' => $booking_id,
            'sender_id' => Auth::id(),
            'message' => $request->message,
        ]);

        $receiver_id = Auth::id() === $booking->customer_id
            ? $booking->mua_id
            : $booking->customer_id;

        NotifyHelper::notify($receiver_id, 'Pesan Baru', 'Anda menerima pesan dari ' . Auth::user()->name);

        return response()->json([
            'message' => 'Message sent',
            'data' => $chat->load('sender')
        ]);
    }
}

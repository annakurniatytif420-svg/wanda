<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\MidtransService;
use Illuminate\Support\Facades\Auth;

class BookingPaymentController extends Controller
{
    protected $midtrans;

    public function __construct(MidtransService $midtrans)
    {
        $this->midtrans = $midtrans;
    }

    public function pay($id)
    {
        $booking = Booking::with('customer')->where('customer_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();

        $snap = $this->midtrans->createTransaction($booking);

        return response()->json([
            'token' => $snap->token,
            'redirect_url' => $snap->redirect_url,
        ]);
    }
}

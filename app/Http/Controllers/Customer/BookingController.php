<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Helpers\NotifyHelper;
use App\Services\ImageUploadService;

class BookingController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    public function index()
    {
        $bookings = Auth::user()->bookingsAsCustomer()->with(['mua', 'service'])->latest()->get();
        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'mua_id'     => 'required|exists:users,id',
            'service_id' => 'required|exists:services,id',
            'date'       => 'required|date|after_or_equal:today',
            'time'       => 'required',
            'payment_method' => 'nullable|string',
        ]);

        $customerProfile = Auth::user()->customerProfile;

        // Ambil service untuk mendapatkan harga
        $service = \App\Models\Service::findOrFail($request->service_id);

        $booking = Booking::create([
            'customer_id'  => Auth::id(),
            'mua_id'       => $request->mua_id,
            'service_id'   => $request->service_id,
            'date'         => $request->date,
            'time'         => $request->time,
            'status'       => 'pending',
            'payment_status' => 'pending',
            'payment_method' => $request->payment_method,
            'total_price'  => $service->price,
            'customer_skin_profile_snapshot' => $customerProfile ? $customerProfile->toArray() : null,
        ]);

        NotifyHelper::notify(
            $booking->mua_id,
            'Booking Baru Masuk',
            'Anda menerima booking dari ' . Auth::user()->name . ' untuk tanggal ' . $booking->date . ' pukul ' . $booking->time
        );

        return response()->json([
            'message' => 'Booking created',
            'data'    => $booking
        ]);
    }

    public function show($id)
    {
        $booking = Booking::where('id', $id)->where('customer_id', auth()->id())->with(['mua', 'service'])->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        return response()->json($booking);
    }

    public function update(Request $request, $id)
    {
        $booking = Booking::where('id', $id)->where('customer_id', auth()->id())->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        // // Log semua data yang diterima
        // \Log::info('Update booking request - All input data:', [
        //     'all' => $request->all(),
        //     'has_payment_method' => $request->has('payment_method'),
        //     'payment_method_value' => $request->input('payment_method'),
        //     'has_payment_proof' => $request->hasFile('payment_proof'),
        //     'has_skin_profile' => $request->has('customer_skin_profile_snapshot'),
        // ]);

        // Jika request adalah untuk update skin profile snapshot
        if ($request->has('customer_skin_profile_snapshot')) {
            $request->validate([
                'customer_skin_profile_snapshot' => 'required|array',
            ]);

            $booking->customer_skin_profile_snapshot = $request->customer_skin_profile_snapshot;
            $booking->save();

            return response()->json([
                'message' => 'Booking updated',
                'data' => $booking,
            ]);
        }

        // Periksa apakah ini adalah request method spoofing (POST dengan _method=PUT)
        $isSpoofedPut = $request->input('_method') === 'PUT';

        // Jika request adalah untuk update payment method dan payment proof
        // Periksa apakah ada data yang dikirim dengan FormData
        $hasPaymentData = $request->has('payment_method') || $request->hasFile('payment_proof') || $request->isMethod('post') || $isSpoofedPut;

        if ($hasPaymentData) {
            // Update payment method jika ada
            if ($request->has('payment_method')) {
                $booking->payment_method = $request->input('payment_method');
            }

            // Update payment proof jika ada file
            if ($request->hasFile('payment_proof')) {
                $filename = $this->imageUploadService->uploadPaymentProof($request->file('payment_proof'));
                $booking->payment_proof = $filename;
            }

            $booking->save();

            return response()->json([
                'message' => 'Payment information updated',
                'data' => $booking,
            ]);
        }

        // \Log::info('No data to update - returning 400 error');
        return response()->json(['message' => 'No data to update'], 400);
    }
}

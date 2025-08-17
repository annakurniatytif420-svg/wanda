<?php
namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use App\Helpers\NotifyHelper;

class BookingController extends Controller
{
    public function index()
    {
        $bookings = Auth::user()->bookingsAsMua()->with(['customer', 'service'])->latest()->get();
        return response()->json($bookings);
    }

    public function summary()
    {
        $muaId = Auth::id();
        
        $pendingCount = Booking::where('mua_id', $muaId)->where('status', 'pending')->count();
        $confirmedCount = Booking::where('mua_id', $muaId)->where('status', 'confirmed')->count();
        $completedCount = Booking::where('mua_id', $muaId)->where('status', 'completed')->count();
        
        // Count reviews for this MUA
        $reviewsCount = Review::whereHas('booking', function ($query) use ($muaId) {
            $query->where('mua_id', $muaId);
        })->count();
        
        // Reminders are confirmed bookings (same as upcoming)
        $remindersCount = $confirmedCount;
        
        // Calculate revenue from completed bookings only
        $today = now()->startOfDay();
        $weekStart = now()->startOfWeek();
        $monthStart = now()->startOfMonth();
        
        $dailyRevenue = Booking::where('mua_id', $muaId)
            ->where('status', 'completed')
            ->whereDate('created_at', $today)
            ->sum('total_price');
            
        $weeklyRevenue = Booking::where('mua_id', $muaId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $weekStart)
            ->sum('total_price');
            
        $monthlyRevenue = Booking::where('mua_id', $muaId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $monthStart)
            ->sum('total_price');
        
        return response()->json([
            'new_bookings' => $pendingCount,
            'upcoming' => $confirmedCount,
            'completed' => $completedCount,
            'reviews' => $reviewsCount,
            'reminders' => $remindersCount,
            'daily_revenue' => $dailyRevenue ?: 0,
            'weekly_revenue' => $weeklyRevenue ?: 0,
            'monthly_revenue' => $monthlyRevenue ?: 0
        ]);
    }

    public function getCustomerDetail($id)
    {
        $booking = Booking::where('id', $id)
            ->where('mua_id', Auth::id())
            ->with(['customer', 'customer.customerProfile'])
            ->firstOrFail();

        // Get customer profile photo URL
        $profilePhotoUrl = null;
        if ($booking->customer->customerProfile && $booking->customer->customerProfile->profile_photo) {
            $supabaseBaseUrl = rtrim(env('SUPABASE_STORAGE_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/images'), '/') . '/profile_photos';
            $profilePhotoUrl = $supabaseBaseUrl . '/' . ltrim($booking->customer->customerProfile->profile_photo, '/');
        } else {
            // Use default avatar
            $supabaseBaseUrl = env('SUPABASE_STORAGE_URL', 'https://fqnrwqaaehzkypgfjdii.supabase.co/storage/v1/object/public/images');
            $profilePhotoUrl = $supabaseBaseUrl . '/default-avatar.jpg';
        }

        return response()->json([
            'id' => $booking->customer->id,
            'name' => $booking->customer->name,
            'email' => $booking->customer->email,
            'phone' => $booking->customer->customerProfile->phone ?? '',
            'address' => $booking->customer->customerProfile->address ?? '',
            'style' => $booking->customer->customerProfile->style ?? '',
            'profile_photo_url' => $profilePhotoUrl,
            'payment_proof_url' => $booking->payment_proof_url ?? '',
            'booking_details' => [
                'service' => $booking->service->name,
                'date' => $booking->date,
                'time' => $booking->time,
                'total_price' => $booking->total_price,
                'status' => $booking->status,
                'payment_status' => $booking->payment_status
            ]
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $booking = Booking::where('id', $id)->where('mua_id', Auth::id())->firstOrFail();

        $request->validate([
            'status' => 'required|in:pending,confirmed,completed,cancelled',
            'payment_status' => 'nullable|in:pending,paid,refunded',
            'date' => 'nullable|date',
            'time' => 'nullable|date_format:H:i'
        ]);

        $booking->update([
            'status' => $request->status,
            'payment_status' => $request->payment_status ?? $booking->payment_status,
            'date' => $request->date ?? $booking->date,
            'time' => $request->time ?? $booking->time,
        ]);

        $message = match ($request->status) {
            'confirmed' => 'Booking Anda telah dikonfirmasi oleh MUA.',
            'cancelled' => 'Booking Anda telah dibatalkan oleh MUA.',
            'completed' => 'Booking Anda telah ditandai selesai oleh MUA.',
            default => null
        };

        if ($message) {
            NotifyHelper::notify(
                $booking->customer_id,
                'Status Booking: ' . ucfirst($request->status),
                $message
            );
        }

        return response()->json([
            'message' => 'Booking updated',
            'booking' => $booking
        ]);
    }
}

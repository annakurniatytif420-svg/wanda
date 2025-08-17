<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Booking;
use App\Models\Review;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    public function index()
    {
        $services = Auth::user()->services;
        return response()->json($services);
    }

    public function getServicesByMuaId($id)
    {
        $services = \App\Models\Service::where('mua_id', $id)->get();
        return response()->json($services);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'duration'     => 'required|string|max:50',
            'photo'        => 'nullable|image|max:2048',
            'makeup_style' => 'nullable|string|max:255',
            'category'     => 'nullable|string|max:100',
        ]);

        $data = $request->only(['name', 'description', 'price', 'duration', 'makeup_style', 'category']);
        $data['mua_id'] = Auth::id();

        if ($request->hasFile('photo')) {
            $filename = $this->imageUploadService->uploadServicePhoto($request->file('photo'));
            $data['photo'] = $filename;
        }

        $service = Service::create($data);

        return response()->json([
            'message' => 'Service created successfully',
            'service' => $service
        ]);
    }

    public function update(Request $request, $id)
    {
        $service = Service::where('id', $id)->where('mua_id', Auth::id())->firstOrFail();

        $request->validate([
            'name'         => 'required|string|max:255',
            'description'  => 'nullable|string',
            'price'        => 'required|numeric|min:0',
            'duration'     => 'required|string|max:50',
            'photo'        => 'nullable|image|max:2048',
            'makeup_style' => 'nullable|string|max:255',
            'category'     => 'nullable|string|max:100',
        ]);

        $data = $request->only(['name', 'description', 'price', 'duration', 'makeup_style', 'category']);

        if ($request->hasFile('photo')) {
            if ($service->photo) {
                $this->imageUploadService->deleteImage($service->photo, 'images/service_photos');
            }

            $filename = $this->imageUploadService->uploadServicePhoto($request->file('photo'));
            $data['photo'] = $filename;
        }

        $service->update($data);

        return response()->json([
            'message' => 'Service updated successfully',
            'service' => $service
        ]);
    }

    public function destroy($id)
    {
        $service = Service::where('id', $id)->where('mua_id', Auth::id())->firstOrFail();
        $service->delete();

        return response()->json(['message' => 'Service deleted']);
    }

    public function analytics()
    {
        $muaId = Auth::id();

        // Total Bookings (completed only)
        $totalBookings = Booking::where('bookings.mua_id', $muaId)
            ->where('status', 'completed')
            ->count();

        // Total Revenue (from completed bookings only)
        $totalRevenue = Booking::where('bookings.mua_id', $muaId)
            ->where('status', 'completed')
            ->sum('total_price');

        // Average Rating (from reviews of this MUA's bookings)
        $averageRating = Review::whereHas('booking', function ($query) use ($muaId) {
            $query->where('bookings.mua_id', $muaId);
        })->avg('rating');

        // Most Popular Category (most booked category from completed bookings)
        $mostPopularCategory = Booking::where('bookings.mua_id', $muaId)
            ->where('status', 'completed')
            ->join('services', 'bookings.service_id', '=', 'services.id')
            ->select('services.category', DB::raw('COUNT(*) as booking_count'))
            ->groupBy('services.category')
            ->orderBy('booking_count', 'desc')
            ->first();

        return response()->json([
            'total_bookings' => $totalBookings,
            'total_revenue' => $totalRevenue ?: 0,
            'average_rating' => $averageRating ? round($averageRating, 1) : 0,
            'most_popular_category' => $mostPopularCategory ? $mostPopularCategory->category : 'No data'
        ]);
    }
}

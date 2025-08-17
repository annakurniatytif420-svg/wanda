<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\CustomerAuthController;
use App\Http\Controllers\Auth\MuaAuthController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\Mua\ServiceController;
use App\Http\Controllers\Mua\PortfolioController;
use App\Http\Controllers\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Mua\BookingController as MuaBookingController;
use App\Http\Controllers\Customer\RecommendationController;
use App\Http\Controllers\Customer\ReviewController;
use App\Http\Controllers\Customer\WishlistController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Mua\ProfileController as MuaProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Customer\BookingPaymentController;
use App\Http\Controllers\Mua\ReportController;

use Fruitcake\Cors\HandleCors;
use Illuminate\Http\Request as HttpRequest;

Route::options('{any}', function (HttpRequest $request) {
    $cors = new HandleCors();
    $response = response()->json([], 200);
    return $cors->addActualRequestHeaders($response, $request);
})->where('any', '.*');

Route::middleware('auth:sanctum')->get('/me', [MeController::class, 'me']);
// Route::middleware('auth:sanctum')->get('/users', [MeController::class, 'index']);
Route::get('/users', [MeController::class, 'index']);
Route::middleware('auth:sanctum')->put('/me', [MeController::class, 'update']);


Route::prefix('auth')->group(function () {
    Route::post('/register/customer', [CustomerAuthController::class, 'register']);
    Route::post('/login/customer', [CustomerAuthController::class, 'login']);

    Route::post('/register/mua', [MuaAuthController::class, 'register']);
    Route::post('/login/mua', [MuaAuthController::class, 'login']);
});

Route::middleware('auth:sanctum')->post('/auth/logout/mua', [MuaAuthController::class, 'logout']);
Route::middleware('auth:sanctum')->post('/auth/logout/customer', [CustomerAuthController::class, 'logout']);

Route::middleware(['auth:sanctum'])->prefix('mua')->group(function () {
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
    Route::get('/services/analytics', [ServiceController::class, 'analytics']);
    Route::get('/{id}/services', [ServiceController::class, 'getServicesByMuaId']);
});

Route::middleware(['auth:sanctum'])->prefix('customer')->group(function () {
    Route::get('/profile', [CustomerProfileController::class, 'show']);
    Route::post('/profile', [CustomerProfileController::class, 'store']);
    Route::put('/profile', [CustomerProfileController::class, 'update']);
});

Route::middleware(['auth:sanctum'])->prefix('mua')->group(function () {
    Route::get('/profile', [MuaProfileController::class, 'show']);
    Route::put('/profile', [MuaProfileController::class, 'update']);
    Route::post('/profile', [MuaProfileController::class, 'store']);
});

Route::prefix('dashboard')->group(function () {
    Route::get('/mua/search', [DashboardController::class, 'index']);
    Route::get('/mua', [DashboardController::class, 'mua']);
    Route::get('/mua-users', [DashboardController::class, 'getAllMuaWithProfile']);
});
Route::middleware(['auth:sanctum'])->prefix('customer')->group(function () {
    Route::get('/bookings', [CustomerBookingController::class, 'index']);
    Route::post('/bookings', [CustomerBookingController::class, 'store']);
    Route::get('/bookings/{id}', [CustomerBookingController::class, 'show']);
    Route::put('/bookings/{id}', [CustomerBookingController::class, 'update']);
});
Route::middleware(['auth:sanctum'])->prefix('mua')->group(function () {
    Route::get('/bookings', [MuaBookingController::class, 'index']);
    Route::get('/bookings/summary', [MuaBookingController::class, 'summary']);
    Route::put('/bookings/{id}/status', [MuaBookingController::class, 'updateStatus']);
    Route::put('/bookings/{id}', [MuaBookingController::class, 'update']);
    Route::get('/bookings/{id}/customer-detail', [MuaBookingController::class, 'getCustomerDetail']);
});

Route::middleware(['auth:sanctum'])->prefix('customer')->group(function () {
    Route::get('/recommendations', [RecommendationController::class, 'index']);
});

Route::middleware(['auth:sanctum'])->prefix('customer')->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store']);
});

Route::get('/mua/{mua_id}/reviews', function ($mua_id) {
    return \App\Models\Review::with('booking.customer')->whereHas('booking', function ($q) use ($mua_id) {
        $q->where('mua_id', $mua_id);
    })->latest()->get()->map(function ($review) {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'created_at' => $review->created_at,
            'customer_name' => $review->booking->customer->name ?? 'Anonymous',
        ];
    });
});

Route::middleware(['auth:sanctum'])->prefix('customer')->group(function () {
    Route::get('/wishlist', [WishlistController::class, 'index']);
    Route::post('/wishlist', [WishlistController::class, 'store']);
    Route::delete('/wishlist/{mua_id}', [WishlistController::class, 'destroy']);
});

Route::get('/mua/{id}', [MuaProfileController::class, 'publicProfile']);
Route::get('/mua/search', [DashboardController::class, 'index']);
Route::get('/mua/{id}/availability', [AvailabilityController::class, 'show']);

Route::middleware('auth:sanctum')->prefix('notifications')->group(function () {
    Route::get('/', [NotificationController::class, 'index']);
    Route::get('/unread-count', [NotificationController::class, 'unreadCount']);
    Route::put('/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/chat/{booking_id}', [ChatController::class, 'index']);
    Route::post('/chat/{booking_id}', [ChatController::class, 'store']);
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/booking/{id}/pay', [BookingPaymentController::class, 'pay']);
});

// Blocked time slots
Route::middleware(['auth:sanctum'])->prefix('blocked-slots')->group(function () {
    Route::get('/', [\App\Http\Controllers\Mua\BlockedTimeSlotController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Mua\BlockedTimeSlotController::class, 'store']);
    Route::delete('/{id}', [\App\Http\Controllers\Mua\BlockedTimeSlotController::class, 'destroy']);
});

Route::middleware(['auth:sanctum'])->prefix('mua')->group(function () {
    Route::get('/reports', [ReportController::class, 'index']);
    
    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::put('/services/{id}', [ServiceController::class, 'update']);
    Route::delete('/services/{id}', [ServiceController::class, 'destroy']);
    Route::get('/services/analytics', [ServiceController::class, 'analytics']);
    Route::get('/{id}/services', [ServiceController::class, 'getServicesByMuaId']);
    
    Route::get('/profile', [MuaProfileController::class, 'show']);
    Route::put('/profile', [MuaProfileController::class, 'update']);
    Route::post('/profile', [MuaProfileController::class, 'store']);
    
    Route::get('/bookings', [MuaBookingController::class, 'index']);
    Route::get('/bookings/summary', [MuaBookingController::class, 'summary']);
    Route::put('/bookings/{id}/status', [MuaBookingController::class, 'updateStatus']);
    Route::put('/bookings/{id}', [MuaBookingController::class, 'update']);
});

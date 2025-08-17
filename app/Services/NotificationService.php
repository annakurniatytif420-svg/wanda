<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Booking;
use Carbon\Carbon;

class NotificationService
{
    public static function createBookingNotification($booking, $type)
    {
        $customer = $booking->customer;
        $mua = $booking->mua;

        switch ($type) {
            case 'booking_created':
                // Notify MUA
                self::createNotification(
                    $mua->user_id,
                    'booking_created',
                    'New Booking Request',
                    "You have a new booking request from {$customer->name} for {$booking->service->name} on {$booking->booking_date}",
                    ['booking_id' => $booking->id, 'customer_id' => $customer->id]
                );

                // Notify Customer
                self::createNotification(
                    $customer->user_id,
                    'booking_created',
                    'Booking Request Sent',
                    "Your booking request for {$booking->service->name} has been sent to {$mua->name}",
                    ['booking_id' => $booking->id, 'mua_id' => $mua->id]
                );
                break;

            case 'booking_confirmed':
                self::createNotification(
                    $customer->user_id,
                    'booking_confirmed',
                    'Booking Confirmed',
                    "Your booking for {$booking->service->name} on {$booking->booking_date} has been confirmed",
                    ['booking_id' => $booking->id, 'mua_id' => $mua->id]
                );
                break;

            case 'booking_rejected':
                self::createNotification(
                    $customer->user_id,
                    'booking_rejected',
                    'Booking Rejected',
                    "Your booking for {$booking->service->name} on {$booking->booking_date} has been rejected",
                    ['booking_id' => $booking->id, 'mua_id' => $mua->id]
                );
                break;

            case 'payment_confirmed':
                self::createNotification(
                    $mua->user_id,
                    'payment_confirmed',
                    'Payment Confirmed',
                    "Payment confirmed for booking {$booking->service->name} on {$booking->booking_date}",
                    ['booking_id' => $booking->id, 'customer_id' => $customer->id]
                );

                self::createNotification(
                    $customer->user_id,
                    'payment_confirmed',
                    'Payment Successful',
                    "Your payment for {$booking->service->name} has been confirmed",
                    ['booking_id' => $booking->id, 'mua_id' => $mua->id]
                );
                break;

            case 'booking_reminder':
                $bookingDate = Carbon::parse($booking->booking_date);
                $daysUntil = $bookingDate->diffInDays(Carbon::now());
                
                $message = $daysUntil == 0 
                    ? "Your booking for {$booking->service->name} is today!" 
                    : "Your booking for {$booking->service->name} is in {$daysUntil} day(s)";

                self::createNotification(
                    $customer->user_id,
                    'booking_reminder',
                    'Booking Reminder',
                    $message,
                    ['booking_id' => $booking->id, 'mua_id' => $mua->id]
                );

                self::createNotification(
                    $mua->user_id,
                    'booking_reminder',
                    'Booking Reminder',
                    $message,
                    ['booking_id' => $booking->id, 'customer_id' => $customer->id]
                );
                break;

            case 'booking_completed':
                self::createNotification(
                    $customer->user_id,
                    'booking_completed',
                    'Booking Completed',
                    "Your booking for {$booking->service->name} has been completed. Please leave a review!",
                    ['booking_id' => $booking->id, 'mua_id' => $mua->id]
                );
                break;
        }
    }

    public static function createNotification($userId, $type, $title, $message, $data = [])
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'sent_at' => now()
        ]);
    }

    public static function sendBookingReminders()
    {
        $tomorrow = Carbon::tomorrow();
        $today = Carbon::today();

        // Get bookings for tomorrow and today
        $bookings = Booking::whereIn('booking_date', [$tomorrow, $today])
            ->where('status', 'confirmed')
            ->get();

        foreach ($bookings as $booking) {
            self::createBookingNotification($booking, 'booking_reminder');
        }
    }
}

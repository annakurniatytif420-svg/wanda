<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\NotificationService;
use App\Models\Booking;
use Carbon\Carbon;

class SendBookingNotifications extends Command
{
    protected $signature = 'notifications:send-booking-reminders';
    protected $description = 'Send booking reminders to customers and MUAs';

    public function handle()
    {
        $this->info('Sending booking reminders...');
        
        // Get bookings for tomorrow
        $tomorrow = Carbon::tomorrow();
        $bookings = Booking::where('booking_date', $tomorrow)
            ->where('status', 'confirmed')
            ->get();

        $count = 0;
        foreach ($bookings as $booking) {
            NotificationService::createBookingNotification($booking, 'booking_reminder');
            $count++;
        }

        // Get bookings for today
        $today = Carbon::today();
        $todayBookings = Booking::where('booking_date', $today)
            ->where('status', 'confirmed')
            ->get();

        foreach ($todayBookings as $booking) {
            NotificationService::createBookingNotification($booking, 'booking_reminder');
            $count++;
        }

        $this->info("Sent {$count} booking reminders");
    }
}

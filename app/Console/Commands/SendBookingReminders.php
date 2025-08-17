<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Booking;
use App\Helpers\NotifyHelper;
use Carbon\Carbon;

class SendBookingReminders extends Command
{
    protected $signature = 'booking:reminder';
    protected $description = 'Kirim notifikasi pengingat H-1 ke customer dan MUA';

    public function handle()
    {
        $targetDate = Carbon::tomorrow()->toDateString(); // tanggal besok

        $bookings = Booking::where('status', 'confirmed')
            ->where('date', $targetDate)
            ->with(['customer', 'mua'])
            ->get();

        foreach ($bookings as $booking) {
            // 🔔 Notifikasi untuk Customer
            NotifyHelper::notify(
                $booking->customer_id,
                'Pengingat Booking Besok',
                'Jangan lupa, Anda punya booking dengan ' . $booking->mua->name . ' pada ' . $booking->date . ' jam ' . $booking->time
            );

            // 🔔 (Opsional) Notifikasi untuk MUA
            NotifyHelper::notify(
                $booking->mua_id,
                'Pengingat Booking Besok',
                'Anda memiliki booking dengan ' . $booking->customer->name . ' pada ' . $booking->date . ' jam ' . $booking->time
            );
        }

        $this->info('Booking reminders sent successfully.');
    }
}
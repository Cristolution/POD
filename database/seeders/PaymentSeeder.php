<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        Order::whereNotIn('status', ['cancelled'])->get()->each(function (Order $order) use ($admin) {
            // 1 payment per order. Status mirrors the order's progression.
            $status = match ($order->status) {
                'pending'    => 'pending',
                'paid', 'processing', 'shipped', 'delivered' => 'confirmed',
                default      => 'pending',
            };

            /** @var Payment $payment */
            $payment = Payment::factory()->create([
                'order_id' => $order->id,
                'status' => $status,
                'confirmed_by_admin_id' => $status === 'confirmed' ? $admin->id : null,
                'confirmed_at' => $status === 'confirmed' ? fake()->dateTimeBetween('-30 days', 'now') : null,
            ]);

            // Attach a proof-of-payment image for confirmed bank transfers
            if ($status === 'confirmed' && $payment->method === 'bank_transfer') {
                Media::factory()->paymentProof()->create([
                    'model_type' => Payment::class,
                    'model_id' => $payment->id,
                ]);
            }
        });
    }
}

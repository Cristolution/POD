<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => (string) Str::uuid(),
            'notifiable_type' => User::class,
            'notifiable_id' => User::factory(),
            'type' => 'App\\Notifications\\GenericNotification',
            'data' => [
                'title' => fake()->sentence(4),
                'message' => fake()->sentence(),
                'url' => '/notifications/' . fake()->uuid(),
            ],
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }

    public function unread(): static
    {
        return $this->state(fn () => ['read_at' => null]);
    }
}

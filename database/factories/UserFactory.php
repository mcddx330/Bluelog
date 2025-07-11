<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'did' => 'did:plc:' . Str::random(10),
            'handle' => fake()->userName() . '.bsky.social',
            'display_name' => fake()->name(),
            'description' => fake()->sentence(),
            'avatar_url' => fake()->imageUrl(),
            'banner_url' => fake()->imageUrl(),
            'followers_count' => fake()->numberBetween(0, 1000),
            'following_count' => fake()->numberBetween(0, 500),
            'posts_count' => fake()->numberBetween(0, 2000),
            'registered_at' => fake()->dateTimeThisYear(),
            'last_login_at' => now(),
            'access_jwt' => Str::random(64),
            'refresh_jwt' => Str::random(64),
            'is_private' => false,
            'is_fetching' => false,
            'is_early_adopter' => false,
            'invisible_badge' => false,
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

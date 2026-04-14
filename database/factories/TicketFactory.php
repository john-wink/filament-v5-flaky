<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Team;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'status' => fake()->randomElement(['open', 'pending', 'closed']),
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
        ];
    }
}

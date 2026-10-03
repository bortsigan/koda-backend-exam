<?php

namespace Database\Factories;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');

        return [
            'client_name' => $this->faker->company(),
            'project_name' => $this->faker->catchPhrase(),
            'description' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(ProjectStatus::cases()),
            'priority' => $this->faker->randomElement(ProjectPriority::cases()),
            'start_date' => $startDate,
            'due_date' => $this->faker->dateTimeBetween($startDate, '+2 months'),
        ];
    }
}

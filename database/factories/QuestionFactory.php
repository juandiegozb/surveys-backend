<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(4),
            'question_text' => $this->faker->sentence() . '?',
            'question_type_id' => QuestionType::factory(),
            'user_id' => User::factory(),
            'options' => null,
            'validation_rules' => null,
            'image_url' => null,
            'attachments' => null,
            'is_required' => $this->faker->boolean(70), // 70% chance of being required
            'is_active' => true,
            'metadata' => [],
            'usage_count' => 0,
        ];
    }

    /**
     * Indicate that the question is required.
     */
    public function required(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => true,
        ]);
    }

    /**
     * Indicate that the question is optional.
     */
    public function optional(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_required' => false,
        ]);
    }

    /**
     * Indicate that the question has multiple choice options.
     */
    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'options' => [
                'Option 1',
                'Option 2',
                'Option 3',
                'Option 4'
            ],
        ]);
    }
}

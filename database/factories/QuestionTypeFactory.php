<?php

namespace Database\Factories;

use App\Models\QuestionType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\QuestionType>
 */
class QuestionTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = [
            ['name' => 'text', 'display_name' => 'Text Input', 'description' => 'Single line text input'],
            ['name' => 'textarea', 'display_name' => 'Text Area', 'description' => 'Multi-line text input'],
            ['name' => 'multiple-choice', 'display_name' => 'Multiple Choice', 'description' => 'Select one from multiple options'],
            ['name' => 'checkbox', 'display_name' => 'Checkbox', 'description' => 'Select multiple from options'],
            ['name' => 'rating', 'display_name' => 'Rating Scale', 'description' => 'Rate on a scale'],
            ['name' => 'comment-only', 'display_name' => 'Comment Only', 'description' => 'Free text comment'],
        ];

        $type = $this->faker->randomElement($types);

        return [
            'name' => $type['name'],
            'display_name' => $type['display_name'],
            'description' => $type['description'],
            'configuration' => [],
            'allows_images' => $this->faker->boolean(30),
            'allows_multiple_answers' => in_array($type['name'], ['checkbox']) ? true : false,
            'is_active' => true,
        ];
    }

    /**
     * Create a text type question type.
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'text',
            'display_name' => 'Text Input',
            'description' => 'Single line text input',
            'allows_multiple_answers' => false,
        ]);
    }

    /**
     * Create a multiple choice question type.
     */
    public function multipleChoice(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'multiple-choice',
            'display_name' => 'Multiple Choice',
            'description' => 'Select one from multiple options',
            'allows_multiple_answers' => false,
        ]);
    }

    /**
     * Create a rating question type.
     */
    public function rating(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'rating',
            'display_name' => 'Rating Scale',
            'description' => 'Rate on a scale from 1 to 5',
            'allows_multiple_answers' => false,
        ]);
    }
}

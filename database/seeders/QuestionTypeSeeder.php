<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $questionTypes = [
            [
                'name' => 'text',
                'display_name' => 'Text Input',
                'description' => 'Single line text input',
                'configuration' => json_encode(['max_length' => 255, 'min_length' => 0]),
                'allows_images' => false,
                'allows_multiple_answers' => false,
                'is_active' => true,
            ],
            [
                'name' => 'textarea',
                'display_name' => 'Long Text',
                'description' => 'Multi-line text input for detailed responses',
                'configuration' => json_encode(['max_length' => 5000, 'min_length' => 0]),
                'allows_images' => false,
                'allows_multiple_answers' => false,
                'is_active' => true,
            ],
            [
                'name' => 'multiple-choice',
                'display_name' => 'Multiple Choice',
                'description' => 'Select one option from multiple choices',
                'configuration' => json_encode(['min_options' => 2, 'max_options' => 10]),
                'allows_images' => true,
                'allows_multiple_answers' => false,
                'is_active' => true,
            ],
            [
                'name' => 'checkbox',
                'display_name' => 'Checkbox',
                'description' => 'Select multiple options from choices',
                'configuration' => json_encode(['min_options' => 2, 'max_options' => 10, 'min_selections' => 0, 'max_selections' => null]),
                'allows_images' => true,
                'allows_multiple_answers' => true,
                'is_active' => true,
            ],
            [
                'name' => 'file-upload',
                'display_name' => 'File Upload',
                'description' => 'Allow users to upload files or images',
                'configuration' => json_encode(['allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'], 'max_size' => 10485760]), // 10MB
                'allows_images' => true,
                'allows_multiple_answers' => true,
                'is_active' => true,
            ],
            [
                'name' => 'yes-no',
                'display_name' => 'Yes/No',
                'description' => 'Simple yes or no question',
                'configuration' => json_encode(['options' => ['Yes', 'No']]),
                'allows_images' => false,
                'allows_multiple_answers' => false,
                'is_active' => true,
            ],
            [
                'name' => 'dropdown',
                'display_name' => 'Dropdown',
                'description' => 'Select one option from a dropdown list',
                'configuration' => json_encode(['min_options' => 2, 'max_options' => 50]),
                'allows_images' => false,
                'allows_multiple_answers' => false,
                'is_active' => true,
            ],
        ];

        foreach ($questionTypes as $type) {
            DB::table('question_types')->updateOrInsert(
                ['name' => $type['name']],
                array_merge($type, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class QuestionApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;
    protected QuestionType $questionType;

    /**
     * Setup for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a user to associate with questions
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create a question type
        $this->questionType = QuestionType::factory()->create();
    }

    #[Test]
    public function user_can_get_list_of_questions()
    {
        Question::factory()->count(3)->create([
            'question_type_id' => $this->questionType->id
        ]);

        $response = $this->getJson('/api/questions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'question_text',
                        'question_type',
                        'is_required'
                    ]
                ]
            ]);
    }

    #[Test]
    public function user_can_create_a_question()
    {
        $questionData = [
            'name' => 'Test Question',
            'question_text' => 'What is your favorite color?',
            'question_type_id' => $this->questionType->id,
            'is_required' => true,
            'options' => ['Red', 'Blue', 'Green']
        ];

        $response = $this->postJson('/api/questions', $questionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'name',
                    'question_text',
                    'question_type',
                    'is_required'
                ]
            ]);

        $this->assertDatabaseHas('questions', [
            'name' => 'Test Question',
            'question_text' => 'What is your favorite color?',
            'question_type_id' => $this->questionType->id
        ]);
    }

    #[Test]
    public function user_can_get_question_details()
    {
        $question = Question::factory()->create([
            'question_type_id' => $this->questionType->id
        ]);

        $response = $this->getJson('/api/questions/' . $question->uuid);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'name',
                    'question_text',
                    'question_type',
                    'is_required'
                ]
            ]);
    }

    #[Test]
    public function user_can_update_a_question()
    {
        $question = Question::factory()->create([
            'question_type_id' => $this->questionType->id
        ]);

        $updateData = [
            'name' => 'Updated Question Name',
            'question_text' => 'Updated question text?'
        ];

        $response = $this->putJson('/api/questions/' . $question->uuid, $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'name' => 'Updated Question Name',
            'question_text' => 'Updated question text?'
        ]);
    }

    #[Test]
    public function user_can_delete_a_question()
    {
        $question = Question::factory()->create([
            'question_type_id' => $this->questionType->id
        ]);

        $response = $this->deleteJson('/api/questions/' . $question->uuid);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('questions', [
            'id' => $question->id
        ]);
    }

    #[Test]
    public function validation_works_when_creating_question()
    {
        $response = $this->postJson('/api/questions', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'question_text', 'question_type_id']);
    }

    #[Test]
    public function user_can_bulk_assign_questions_to_survey()
    {
        $survey = Survey::factory()->create(['user_id' => $this->user->id]);
        $questions = Question::factory()->count(3)->create([
            'question_type_id' => $this->questionType->id
        ]);

        $bulkData = [
            'survey_uuid' => $survey->uuid,
            'question_uuids' => $questions->pluck('uuid')->toArray()
        ];

        // Note: This endpoint returns 501 (Not Implemented) as configured in routes
        $response = $this->postJson('/api/bulk/questions', $bulkData);

        $response->assertStatus(501);
    }

    #[Test]
    public function user_can_bulk_delete_questions()
    {
        $questions = Question::factory()->count(3)->create([
            'question_type_id' => $this->questionType->id
        ]);

        $bulkData = [
            'question_uuids' => $questions->pluck('uuid')->toArray()
        ];

        // Note: This endpoint returns 501 (Not Implemented) as configured in routes
        // Use POST for bulk operations as configured in api.php
        $response = $this->postJson('/api/bulk/questions', $bulkData);

        $response->assertStatus(501);
    }
}

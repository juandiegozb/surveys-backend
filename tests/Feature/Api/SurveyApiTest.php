<?php

namespace Tests\Feature\Api;

use App\Models\Survey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SurveyApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $user;

    /**
     * Setup for tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create a user to associate with surveys
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    #[Test]
    public function user_can_get_list_of_surveys()
    {
        // Create 5 surveys
        $surveys = Survey::factory()->count(5)->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/surveys');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'uuid',
                        'name',
                        'description',
                        'status',
                        'created_at',
                        'updated_at'
                    ]
                ]
            ]);
    }

    #[Test]
    public function user_can_create_a_survey()
    {
        $surveyData = [
            'name' => 'Test Survey',
            'description' => 'This is a test survey',
            'status' => 'draft',
            'is_public' => false,
        ];

        $response = $this->postJson('/api/surveys', $surveyData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'name',
                    'description',
                    'status',
                    'is_public'
                ]
            ]);

        $this->assertDatabaseHas('surveys', [
            'name' => 'Test Survey',
            'description' => 'This is a test survey',
            'user_id' => $this->user->id
        ]);
    }

    #[Test]
    public function user_can_get_survey_details()
    {
        $survey = Survey::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->getJson('/api/surveys/' . $survey->uuid);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'uuid',
                    'name',
                    'description',
                    'status',
                    'is_public'
                ]
            ]);
    }

    #[Test]
    public function user_can_update_a_survey()
    {
        $survey = Survey::factory()->create([
            'user_id' => $this->user->id
        ]);

        $updateData = [
            'name' => 'Updated Survey Name',
            'description' => 'Updated description'
        ];

        $response = $this->putJson('/api/surveys/' . $survey->uuid, $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('surveys', [
            'id' => $survey->id,
            'name' => 'Updated Survey Name',
            'description' => 'Updated description'
        ]);
    }

    #[Test]
    public function user_can_delete_a_survey()
    {
        $survey = Survey::factory()->create([
            'user_id' => $this->user->id
        ]);

        $response = $this->deleteJson('/api/surveys/' . $survey->uuid);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('surveys', [
            'id' => $survey->id
        ]);
    }

    #[Test]
    public function validation_works_when_creating_survey()
    {
        $response = $this->postJson('/api/surveys', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}

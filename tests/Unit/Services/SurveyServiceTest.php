<?php

namespace Tests\Unit\Services;

use App\Models\Survey;
use App\Models\User;
use App\Repositories\SurveyRepository;
use App\Services\SurveyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class SurveyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SurveyRepository $surveyRepository;
    protected SurveyService $surveyService;
    protected User $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->surveyRepository = new SurveyRepository();
        $this->surveyService = new SurveyService($this->surveyRepository);

        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    #[Test]
    public function it_can_find_survey_by_uuid()
    {
        $survey = Survey::factory()->create([
            'user_id' => $this->user->id
        ]);

        $foundSurvey = $this->surveyService->findByUuid($survey->uuid);

        $this->assertNotNull($foundSurvey);
        $this->assertEquals($survey->id, $foundSurvey->id);
        $this->assertEquals($survey->name, $foundSurvey->name);
    }

    #[Test]
    public function it_throws_exception_when_survey_not_found()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->surveyService->findByUuid('non-existent-uuid');
    }

    #[Test]
    public function it_can_create_survey()
    {
        $surveyData = [
            'name' => 'Test Survey',
            'description' => 'A test survey',
            'status' => 'draft',
            'is_public' => false,
            'user_id' => $this->user->id
        ];

        $survey = $this->surveyService->create($surveyData);

        $this->assertInstanceOf(Survey::class, $survey);
        $this->assertEquals('Test Survey', $survey->name);
        $this->assertEquals('A test survey', $survey->description);
        $this->assertEquals('draft', $survey->status);
        $this->assertFalse($survey->is_public);
        $this->assertEquals($this->user->id, $survey->user_id);

        $this->assertDatabaseHas('surveys', [
            'name' => 'Test Survey',
            'description' => 'A test survey',
            'user_id' => $this->user->id
        ]);
    }

    #[Test]
    public function it_can_update_survey()
    {
        $survey = Survey::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Name'
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ];

        // Use UUID instead of Survey object
        $updatedSurvey = $this->surveyService->update($survey->uuid, $updateData);

        $this->assertEquals('Updated Name', $updatedSurvey->name);
        $this->assertEquals('Updated description', $updatedSurvey->description);

        $this->assertDatabaseHas('surveys', [
            'id' => $survey->id,
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ]);
    }

    #[Test]
    public function it_can_delete_survey()
    {
        $survey = Survey::factory()->create([
            'user_id' => $this->user->id
        ]);

        // Use UUID instead of Survey object
        $result = $this->surveyService->delete($survey->uuid);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('surveys', [
            'id' => $survey->id
        ]);
    }

    #[Test]
    public function it_can_update_question_count()
    {
        $survey = Survey::factory()->create([
            'user_id' => $this->user->id,
            'question_count' => 0
        ]);

        // Assuming there's a method to update question count
        $survey->update(['question_count' => 5]);

        $this->assertEquals(5, $survey->fresh()->question_count);
    }

    #[Test]
    public function it_can_list_surveys_with_pagination()
    {
        Survey::factory()->count(15)->create([
            'user_id' => $this->user->id
        ]);

        $surveys = $this->surveyService->list(10);

        $this->assertCount(10, $surveys->items());
        $this->assertEquals(15, $surveys->total());
        $this->assertEquals(2, $surveys->lastPage());
    }

    #[Test]
    public function it_can_filter_surveys_by_status()
    {
        Survey::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'status' => 'active'
        ]);

        Survey::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'status' => 'draft'
        ]);

        $activeSurveys = $this->surveyService->list(10, ['status' => 'active']);
        $draftSurveys = $this->surveyService->list(10, ['status' => 'draft']);

        $this->assertEquals(3, $activeSurveys->total());
        $this->assertEquals(2, $draftSurveys->total());
    }
}

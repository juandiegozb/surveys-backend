<?php

namespace Tests\Unit\Services;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\Survey;
use App\Models\User;
use App\Repositories\QuestionRepository;
use App\Repositories\SurveyRepository;
use App\Services\QuestionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class QuestionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected QuestionRepository $questionRepository;
    protected SurveyRepository $surveyRepository;
    protected QuestionService $questionService;
    protected User $user;
    protected QuestionType $questionType;

    public function setUp(): void
    {
        parent::setUp();

        $this->questionRepository = new QuestionRepository();
        $this->surveyRepository = new SurveyRepository();
        $this->questionService = new QuestionService($this->questionRepository, $this->surveyRepository);

        $this->user = User::factory()->create();
        $this->questionType = QuestionType::factory()->create();
        Auth::login($this->user);
    }

    #[Test]
    public function it_can_find_question_by_uuid()
    {
        $question = Question::factory()->create([
            'question_type_id' => $this->questionType->id
        ]);

        $foundQuestion = $this->questionService->findByUuid($question->uuid);

        $this->assertNotNull($foundQuestion);
        $this->assertEquals($question->id, $foundQuestion->id);
        $this->assertEquals($question->name, $foundQuestion->name);
    }

    #[Test]
    public function it_throws_exception_when_question_not_found()
    {
        $this->expectException(NotFoundHttpException::class);

        $this->questionService->findByUuid('non-existent-uuid');
    }

    #[Test]
    public function it_can_create_question()
    {
        $questionData = [
            'name' => 'Test Question',
            'question_text' => 'What is your favorite color?',
            'question_type_id' => $this->questionType->id,
            'is_required' => true,
            'options' => ['Red', 'Blue', 'Green']
        ];

        $question = $this->questionService->create($questionData);

        $this->assertInstanceOf(Question::class, $question);
        $this->assertEquals('Test Question', $question->name);
        $this->assertEquals('What is your favorite color?', $question->question_text);
        $this->assertEquals($this->questionType->id, $question->question_type_id);
        $this->assertTrue($question->is_required);

        $this->assertDatabaseHas('questions', [
            'name' => 'Test Question',
            'question_text' => 'What is your favorite color?',
            'question_type_id' => $this->questionType->id,
            'is_required' => true
        ]);
    }

    #[Test]
    public function it_can_update_question()
    {
        $question = Question::factory()->create([
            'question_type_id' => $this->questionType->id,
            'name' => 'Original Question'
        ]);

        $updateData = [
            'name' => 'Updated Question',
            'question_text' => 'Updated question text?'
        ];

        $updatedQuestion = $this->questionService->update($question->uuid, $updateData);

        $this->assertEquals('Updated Question', $updatedQuestion->name);
        $this->assertEquals('Updated question text?', $updatedQuestion->question_text);

        $this->assertDatabaseHas('questions', [
            'id' => $question->id,
            'name' => 'Updated Question',
            'question_text' => 'Updated question text?'
        ]);
    }

    #[Test]
    public function it_can_delete_question()
    {
        $question = Question::factory()->create([
            'question_type_id' => $this->questionType->id
        ]);

        $result = $this->questionService->delete($question->uuid);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('questions', [
            'id' => $question->id
        ]);
    }

    #[Test]
    public function it_can_bulk_assign_questions_to_survey()
    {
        $survey = Survey::factory()->create(['user_id' => $this->user->id]);
        $questions = Question::factory()->count(3)->create([
            'question_type_id' => $this->questionType->id
        ]);

        $questionUuids = $questions->pluck('uuid')->toArray();

        $result = $this->questionService->bulkAssignToSurvey($survey->uuid, $questionUuids);

        $this->assertTrue($result);

        // Check that questions are associated with the survey
        foreach ($questions as $question) {
            $this->assertDatabaseHas('survey_questions', [
                'survey_id' => $survey->id,
                'question_id' => $question->id
            ]);
        }
    }

    #[Test]
    public function it_can_bulk_delete_questions()
    {
        $questions = Question::factory()->count(3)->create([
            'question_type_id' => $this->questionType->id
        ]);

        $questionUuids = $questions->pluck('uuid')->toArray();

        $result = $this->questionService->bulkDelete($questionUuids);

        $this->assertTrue($result);

        // Check that all questions are deleted
        foreach ($questions as $question) {
            $this->assertDatabaseMissing('questions', [
                'id' => $question->id
            ]);
        }
    }
}

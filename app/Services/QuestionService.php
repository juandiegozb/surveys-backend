<?php

namespace App\Services;

use App\Models\Question;
use App\Repositories\QuestionRepository;
use App\Repositories\SurveyRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QuestionService
{
    protected QuestionRepository $questionRepository;
    protected SurveyRepository $surveyRepository;

    public function __construct(
        QuestionRepository $questionRepository,
        SurveyRepository $surveyRepository
    ) {
        $this->questionRepository = $questionRepository;
        $this->surveyRepository = $surveyRepository;
    }

    /**
     * Get a paginated list of questions with optional filtering
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function list(int $perPage, array $filters = []): LengthAwarePaginator
    {
        return $this->questionRepository->paginate($perPage, $filters);
    }

    /**
     * Find a question by UUID
     *
     * @param string $uuid
     * @return Question
     * @throws NotFoundHttpException
     */
    public function findByUuid(string $uuid): Question
    {
        $question = $this->questionRepository->findByUuid($uuid);

        if (!$question) {
            throw new NotFoundHttpException('Question not found');
        }

        // Load the question type
        $question->load('questionType');

        return $question;
    }

    /**
     * Create a new question
     *
     * @param array $data
     * @return Question
     */
    public function create(array $data): Question
    {
        // Get authenticated user or create a default one
        $userId = Auth::id();
        if (!$userId) {
            // Create or get a default user for testing
            $user = \App\Models\User::firstOrCreate([
                'email' => 'admin@surveys.local'
            ], [
                'name' => 'Survey Admin',
                'email' => 'admin@surveys.local',
                'password' => bcrypt('password'),
            ]);

            // Mark email as verified if it's a new user
            if (!$user->email_verified_at) {
                $user->email_verified_at = now();
                $user->save();
            }

            $userId = $user->id;
        }

        // Handle survey-specific question creation
        $surveyUuid = $data['survey_uuid'] ?? null;
        unset($data['survey_uuid']); // Remove from data array as it's not a field in questions table

        // Set default values if not provided
        $data['user_id'] = $userId;
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_required'] = $data['is_required'] ?? false;
        $data['usage_count'] = 0;

        // Use database transaction to ensure consistency
        return DB::transaction(function () use ($data, $surveyUuid) {
            $question = $this->questionRepository->create($data);

            // If survey_uuid is provided, link the question to the survey
            if ($surveyUuid) {
                try {
                    // Use fresh data to avoid cache issues
                    $survey = $this->surveyRepository->findByUuidFresh($surveyUuid);
                    if ($survey) {
                        // Add the question to the survey with proper ordering
                        $maxOrder = $survey->questions()->max('survey_questions.order') ?? 0;

                        $survey->questions()->attach($question->id, [
                            'order' => $maxOrder + 1,
                            'is_active' => true,
                            'survey_specific_settings' => json_encode([])
                        ]);

                        // Clear survey cache to ensure fresh data is loaded
                        $survey->clearCache();

                        // Update survey question count with fresh data
                        $survey->refresh(); // Refresh to get updated relationships
                        $questionCount = $survey->questions()->count();
                        $survey->question_count = $questionCount;
                        $survey->save();

                        \Illuminate\Support\Facades\Log::info('Question linked to survey successfully', [
                            'question_id' => $question->id,
                            'question_uuid' => $question->uuid,
                            'survey_id' => $survey->id,
                            'survey_uuid' => $survey->uuid,
                            'question_count' => $questionCount,
                            'survey_questions_table_count' => DB::table('survey_questions')->where('survey_id', $survey->id)->count()
                        ]);
                    } else {
                        \Illuminate\Support\Facades\Log::warning('Survey not found for question linking', [
                            'survey_uuid' => $surveyUuid,
                            'question_id' => $question->id
                        ]);
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error linking question to survey', [
                        'survey_uuid' => $surveyUuid,
                        'question_id' => $question->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    // Re-throw the exception to rollback the transaction
                    throw $e;
                }
            }

            return $question;
        });
    }

    /**
     * Update an existing question
     *
     * @param string $uuid
     * @param array $data
     * @return Question
     * @throws NotFoundHttpException
     */
    public function update(string $uuid, array $data): Question
    {
        $question = $this->findByUuid($uuid);

        return $this->questionRepository->update($question, $data);
    }

    /**
     * Delete a question
     *
     * @param string $uuid
     * @return bool
     * @throws NotFoundHttpException
     */
    public function delete(string $uuid): bool
    {
        $question = $this->findByUuid($uuid);

        return $this->questionRepository->delete($question);
    }

    /**
     * Get questions available for assignment to a survey (not already assigned)
     *
     * @param string $surveyUuid
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAvailableForSurvey(string $surveyUuid, array $filters = []): \Illuminate\Database\Eloquent\Collection
    {
        // Get the survey to exclude its questions
        $survey = $this->surveyRepository->findByUuidFresh($surveyUuid);
        if (!$survey) {
            return collect([]);
        }

        // Get IDs of questions already assigned to this survey
        $assignedQuestionIds = $survey->questions()->pluck('questions.id')->toArray();

        // Build query for available questions
        $query = Question::with('questionType')
            ->where('is_active', true)
            ->whereNotIn('id', $assignedQuestionIds);

        // Apply filters
        if (isset($filters['search']) && $filters['search']) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('question_text', 'like', '%' . $filters['search'] . '%');
            });
        }

        if (isset($filters['question_type_id']) && $filters['question_type_id']) {
            $query->where('question_type_id', $filters['question_type_id']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Bulk assign questions to a survey
     *
     * @param string $surveyUuid
     * @param array $questionUuids
     * @param array $settings Optional settings for each question
     * @return bool
     * @throws NotFoundHttpException
     */
    public function bulkAssignToSurvey(string $surveyUuid, array $questionUuids, array $settings = []): bool
    {
        // Find the survey
        $survey = $this->surveyRepository->findByUuid($surveyUuid);
        if (!$survey) {
            throw new NotFoundHttpException('Survey not found');
        }

        // Get questions by UUIDs
        $questions = $this->questionRepository->findManyByUuids($questionUuids);

        // Check if all questions were found
        if ($questions->count() !== count($questionUuids)) {
            throw new NotFoundHttpException('One or more questions not found');
        }

        DB::transaction(function () use ($survey, $questions, $questionUuids, $settings) {
            $attachData = [];

            // Prepare data for attaching questions to survey
            foreach ($questions as $question) {
                $questionSettings = [];
                $order = null;
                $isActive = true;

                // Find settings for this question if provided
                if (!empty($settings)) {
                    foreach ($settings as $setting) {
                        if ($setting['question_uuid'] === $question->uuid) {
                            $order = $setting['order'] ?? null;
                            $isActive = $setting['is_active'] ?? true;
                            $questionSettings = $setting['survey_specific_settings'] ?? [];
                            break;
                        }
                    }
                }

                // If order is not specified, use the current max order + 1
                if ($order === null) {
                    $maxOrder = $survey->questions()->max('survey_questions.order') ?? -1;
                    $order = $maxOrder + 1;
                }

                $attachData[$question->id] = [
                    'order' => $order,
                    'is_active' => $isActive,
                    'survey_specific_settings' => json_encode($questionSettings),
                ];

                // Increment usage count for the question
                $this->questionRepository->incrementUsageCount($question);
            }

            // Sync questions to the survey
            $survey->questions()->syncWithoutDetaching($attachData);

            // Update question count directly
            $count = $survey->questions()->count();
            $survey->question_count = $count;
            $survey->save();
        });

        return true;
    }

    /**
     * Bulk delete questions
     *
     * @param array $questionUuids
     * @return bool
     */
    public function bulkDelete(array $questionUuids): bool
    {
        $questions = $this->questionRepository->findManyByUuids($questionUuids);

        DB::transaction(function () use ($questions) {
            foreach ($questions as $question) {
                // We need to get all related surveys first to update their question counts later
                $surveys = $question->surveys()->get();

                // Delete the question
                $this->questionRepository->delete($question);

                // Update question count for each affected survey directly
                foreach ($surveys as $survey) {
                    $count = $survey->questions()->count();
                    $survey->question_count = $count;
                    $survey->save();
                }
            }
        });

        return true;
    }
}

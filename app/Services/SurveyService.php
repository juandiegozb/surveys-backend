<?php

namespace App\Services;

use App\Models\Survey;
use App\Models\User;
use App\Repositories\SurveyRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SurveyService
{
    protected $surveyRepository;

    public function __construct(SurveyRepository $surveyRepository)
    {
        $this->surveyRepository = $surveyRepository;
    }

    /**
     * Get a paginated list of surveys with optional filtering
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function list(int $perPage, array $filters = []): LengthAwarePaginator
    {
        return $this->surveyRepository->paginate($perPage, $filters);
    }

    /**
     * Find a survey by UUID
     *
     * @param string $uuid
     * @return Survey
     * @throws NotFoundHttpException
     */
    public function findByUuid(string $uuid): Survey
    {
        // Use fresh data (no cache) to ensure we get updated questions
        $survey = $this->surveyRepository->findByUuidFresh($uuid);

        if (!$survey) {
            throw new NotFoundHttpException('Survey not found');
        }

        // Load the questions for the survey details page
        $survey->load(['questions' => function ($query) {
            $query->with('questionType');
        }]);

        return $survey;
    }

    /**
     * Create a new survey
     *
     * @param array $data
     * @return Survey
     */
    public function create(array $data): Survey
    {
        // Set default values if not provided
        $data['status'] = $data['status'] ?? 'draft';

        // Get authenticated user or create a default one
        $userId = Auth::id();
        if (!$userId) {
            // Create or get a default user for testing
            $user = User::firstOrCreate([
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

        $data['user_id'] = $userId;
        $data['question_count'] = 0;
        $data['response_count'] = 0;

        return $this->surveyRepository->create($data);
    }

    /**
     * Update an existing survey
     *
     * @param string $uuid
     * @param array $data
     * @return Survey
     * @throws NotFoundHttpException
     */
    public function update(string $uuid, array $data): Survey
    {
        $survey = $this->findByUuid($uuid);

        return $this->surveyRepository->update($survey, $data);
    }

    /**
     * Delete a survey
     *
     * @param string $uuid
     * @return bool
     * @throws NotFoundHttpException
     */
    public function delete(string $uuid): bool
    {
        $survey = $this->findByUuid($uuid);

        return $this->surveyRepository->delete($survey);
    }

    /**
     * Count questions in a survey and update the question_count field
     *
     * @param Survey $survey
     * @return void
     */
    public function updateQuestionCount(Survey $survey): void
    {
        $count = $survey->questions()->count();
        $this->surveyRepository->updateQuestionCount($survey, $count);
    }
}

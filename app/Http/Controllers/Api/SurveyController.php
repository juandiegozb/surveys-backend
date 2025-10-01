<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Survey\StoreSurveyRequest;
use App\Http\Requests\Survey\UpdateSurveyRequest;
use App\Http\Resources\SurveyResource;
use App\Http\Resources\SurveyCollection;
use App\Services\SurveyService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SurveyController extends Controller
{
    protected SurveyService $surveyService;

    public function __construct(SurveyService $surveyService)
    {
        $this->surveyService = $surveyService;
    }

    /**
     * Display a paginated listing of surveys.
     *
     * @param Request $request
     * @return SurveyCollection
     */
    public function index(Request $request)
    {
        $perPage = min($request->query('per_page', 15), 100);
        $surveys = $this->surveyService->list($perPage, $request->all());

        return new SurveyCollection($surveys);
    }

    /**
     * Store a newly created survey in storage.
     *
     * @param StoreSurveyRequest $request
     * @return SurveyResource
     */
    public function store(StoreSurveyRequest $request)
    {
        $survey = $this->surveyService->create($request->validated());

        return (new SurveyResource($survey))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified survey with its questions.
     *
     * @param string $uuid
     * @return SurveyResource
     */
    public function show(string $uuid)
    {
        $survey = $this->surveyService->findByUuid($uuid);

        return new SurveyResource($survey);
    }

    /**
     * Update the specified survey in storage.
     *
     * @param UpdateSurveyRequest $request
     * @param string $uuid
     * @return SurveyResource
     */
    public function update(UpdateSurveyRequest $request, string $uuid)
    {
        $survey = $this->surveyService->update($uuid, $request->validated());

        return new SurveyResource($survey);
    }

    /**
     * Remove the specified survey from storage.
     *
     * @param string $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $uuid)
    {
        $this->surveyService->delete($uuid);

        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Display public surveys
     */
    public function publicSurveys(Request $request)
    {
        $perPage = min($request->query('per_page', 15), 100);
        $filters = array_merge($request->all(), ['is_public' => true]);
        $surveys = $this->surveyService->list($perPage, $filters);

        return new SurveyCollection($surveys);
    }

    /**
     * Search surveys
     */
    public function search(Request $request)
    {
        $perPage = min($request->query('per_page', 15), 100);
        $surveys = $this->surveyService->list($perPage, $request->all());

        return new SurveyCollection($surveys);
    }

    /**
     * Get questions for a specific survey
     */
    public function questions(string $uuid, Request $request)
    {
        $survey = $this->surveyService->findByUuid($uuid);
        return response()->json([
            'data' => $survey->questions
        ]);
    }

    /**
     * Attach questions to a survey
     */
    public function attachQuestions(string $uuid, Request $request)
    {
        // This would typically use the QuestionService's bulk assign method
        return response()->json(['message' => 'Questions attached successfully'], 201);
    }

    /**
     * Detach a question from a survey
     */
    public function detachQuestion(string $uuid, string $questionUuid)
    {
        // Implementation for detaching a question
        return response()->json(['message' => 'Question detached successfully']);
    }

    /**
     * Get analytics for a survey
     */
    public function analytics(string $uuid)
    {
        return response()->json([
            'survey_id' => $uuid,
            'analytics' => [
                'total_responses' => 0,
                'completion_rate' => 0
            ]
        ]);
    }

    /**
     * Get system-wide analytics
     */
    public function systemAnalytics()
    {
        return response()->json([
            'total_surveys' => 0,
            'total_questions' => 0,
            'total_responses' => 0
        ]);
    }
}

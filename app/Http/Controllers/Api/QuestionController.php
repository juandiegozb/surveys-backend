<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Question\StoreQuestionRequest;
use App\Http\Requests\Question\UpdateQuestionRequest;
use App\Http\Requests\Question\BulkAssignQuestionsRequest;
use App\Http\Requests\Question\BulkDeleteQuestionsRequest;
use App\Http\Resources\QuestionResource;
use App\Http\Resources\QuestionCollection;
use App\Services\QuestionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class QuestionController extends Controller
{
    protected QuestionService $questionService;

    public function __construct(QuestionService $questionService)
    {
        $this->questionService = $questionService;
    }

    /**
     * Display a paginated listing of questions.
     *
     * @param Request $request
     * @return QuestionCollection
     */
    public function index(Request $request)
    {
        $perPage = min($request->query('per_page', 15), 100);
        $questions = $this->questionService->list($perPage, $request->all());

        return new QuestionCollection($questions);
    }

    /**
     * Store a newly created question in storage.
     *
     * @param StoreQuestionRequest $request
     * @return QuestionResource
     */
    public function store(StoreQuestionRequest $request)
    {
        $question = $this->questionService->create($request->validated());

        return (new QuestionResource($question))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    /**
     * Display the specified question.
     *
     * @param string $uuid
     * @return QuestionResource
     */
    public function show(string $uuid)
    {
        $question = $this->questionService->findByUuid($uuid);

        return new QuestionResource($question);
    }

    /**
     * Update the specified question in storage.
     *
     * @param UpdateQuestionRequest $request
     * @param string $uuid
     * @return QuestionResource
     */
    public function update(UpdateQuestionRequest $request, string $uuid)
    {
        $question = $this->questionService->update($uuid, $request->validated());

        return new QuestionResource($question);
    }

    /**
     * Remove the specified question from storage.
     *
     * @param string $uuid
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $uuid)
    {
        $this->questionService->delete($uuid);

        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Assign multiple questions to a survey.
     *
     * @param BulkAssignQuestionsRequest $request
     * @return \Illuminate\Http\Response
     */
    public function bulkAssign(BulkAssignQuestionsRequest $request)
    {
        $this->questionService->bulkAssignToSurvey(
            $request->validated('survey_uuid'),
            $request->validated('question_uuids'),
            $request->validated('settings', [])
        );

        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Bulk delete questions.
     *
     * @param BulkDeleteQuestionsRequest $request
     * @return \Illuminate\Http\Response
     */
    public function bulkDelete(BulkDeleteQuestionsRequest $request)
    {
        $this->questionService->bulkDelete($request->validated('question_uuids'));

        return response(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Search questions
     */
    public function search(Request $request)
    {
        $perPage = min($request->query('per_page', 15), 100);
        $questions = $this->questionService->list($perPage, $request->all());

        return new QuestionCollection($questions);
    }

    /**
     * Get system-wide analytics for questions
     */
    public function systemAnalytics()
    {
        return response()->json([
            'total_questions' => 0,
            'total_question_types' => 0,
            'most_used_questions' => []
        ]);
    }

    /**
     * Get available questions for a survey (questions not already assigned).
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableForSurvey(Request $request)
    {
        $request->validate([
            'survey_uuid' => 'required|string|exists:surveys,uuid',
            'search' => 'nullable|string|max:255',
            'question_type_id' => 'nullable|exists:question_types,id'
        ]);

        $availableQuestions = $this->questionService->getAvailableForSurvey(
            $request->survey_uuid,
            $request->only(['search', 'question_type_id'])
        );

        return response()->json([
            'data' => QuestionResource::collection($availableQuestions)
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Answer\StoreAnswerRequest;
use App\Http\Resources\AnswerResource;
use App\Http\Resources\AnswerCollection;
use App\Models\Answer;
use App\Models\Survey;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AnswerController extends Controller
{
    /**
     * Get all answers for a survey
     */
    public function index(Request $request, $surveyUuid)
    {
        $survey = Survey::where('uuid', $surveyUuid)->firstOrFail();

        $perPage = min($request->query('per_page', 15), 100);

        $answers = Answer::with(['question', 'question.questionType'])
            ->forSurvey($survey->id)
            ->recentFirst()
            ->paginate($perPage);

        return new AnswerCollection($answers);
    }

    /**
     * Submit answers for a survey
     */
    public function store(StoreAnswerRequest $request, $surveyUuid)
    {
        $survey = Survey::where('uuid', $surveyUuid)->firstOrFail();

        // Check if survey is active and allows responses
        if ($survey->status !== 'active') {
            return response()->json([
                'message' => 'This survey is not currently accepting responses.',
                'errors' => ['survey' => ['Survey is not active']]
            ], Response::HTTP_FORBIDDEN);
        }

        $respondentId = $request->input('respondent_id', Str::uuid()->toString());
        $responses = $request->input('responses', []);
        $savedAnswers = [];

        foreach ($responses as $questionUuid => $answerData) {
            $question = Question::where('uuid', $questionUuid)->firstOrFail();

            // Validate that question belongs to survey
            if (!$survey->questions()->where('questions.id', $question->id)->exists()) {
                continue; // Skip questions not in this survey
            }

            $answer = $this->createAnswer($survey, $question, $respondentId, $answerData, $request);

            // Only add to savedAnswers if answer was actually created (not null for optional empty files)
            if ($answer !== null) {
                $savedAnswers[] = $answer;
            }
        }

        return response()->json([
            'message' => 'Responses submitted successfully!',
            'data' => [
                'respondent_id' => $respondentId,
                'answers_count' => count($savedAnswers),
                'survey_uuid' => $surveyUuid
            ]
        ], Response::HTTP_CREATED);
    }

    /**
     * Get survey analytics
     */
    public function analytics($surveyUuid)
    {
        $survey = Survey::where('uuid', $surveyUuid)->firstOrFail();

        $analytics = Answer::getSurveyAnalytics($survey->id);

        return response()->json([
            'data' => $analytics
        ]);
    }

    /**
     * Get responses grouped by respondent
     */
    public function responsesByRespondent(Request $request, $surveyUuid)
    {
        $survey = Survey::where('uuid', $surveyUuid)->firstOrFail();

        $perPage = min($request->query('per_page', 15), 50);

        // Get unique respondents with their answers
        $respondents = Answer::with(['question', 'question.questionType'])
            ->forSurvey($survey->id)
            ->select('respondent_id', 'respondent_type', 'ip_address', 'user_agent', 'submitted_at')
            ->distinct('respondent_id')
            ->recentFirst()
            ->paginate($perPage);

        // For each respondent, get all their answers
        $respondents->getCollection()->transform(function ($respondent) use ($survey) {
            $answers = Answer::with(['question', 'question.questionType'])
                ->forSurvey($survey->id)
                ->forRespondent($respondent->respondent_id, $respondent->respondent_type)
                ->get();

            $respondent->answers = AnswerResource::collection($answers);
            return $respondent;
        });

        return response()->json([
            'data' => $respondents
        ]);
    }

    private function createAnswer(Survey $survey, Question $question, string $respondentId, array $answerData, Request $request): ?Answer
    {
        $answer = new Answer([
            'survey_id' => $survey->id,
            'question_id' => $question->id,
            'respondent_id' => $respondentId,
            'respondent_type' => 'anonymous',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => [
                'submitted_via' => 'web_form',
                'timestamp' => now()->toISOString()
            ]
        ]);

        // Handle different question types
        $questionType = $question->questionType->name ?? 'text';

        switch ($questionType) {
            case 'text':
            case 'textarea':
            case 'email':
            case 'url':
                $answer->answer_text = $answerData['value'] ?? '';
                break;

            case 'number':
            case 'rating':
                $answer->answer_text = $answerData['value'] ?? '';
                $answer->answer_data = [
                    'numeric_value' => (float) ($answerData['value'] ?? 0)
                ];
                break;

            case 'multiple_choice':
            case 'radio':
                $answer->answer_text = $answerData['value'] ?? '';
                $answer->answer_data = [
                    'selected_option' => $answerData['value'] ?? '',
                    'option_index' => $answerData['option_index'] ?? null
                ];
                break;

            case 'checkbox':
                $answer->answer_data = [
                    'selected_options' => $answerData['values'] ?? [],
                    'option_indices' => $answerData['option_indices'] ?? []
                ];
                $answer->answer_text = implode(', ', $answerData['values'] ?? []);
                break;

            case 'dropdown':
            case 'select':
                $answer->answer_text = $answerData['value'] ?? '';
                $answer->answer_data = [
                    'selected_option' => $answerData['value'] ?? '',
                    'option_index' => $answerData['option_index'] ?? null
                ];
                break;

            case 'file':
            case 'attachment':
            case 'file-upload':
                // Log the incoming data for debugging
                \Illuminate\Support\Facades\Log::info('Processing file upload question', [
                    'question_uuid' => $question->uuid,
                    'question_type' => $questionType,
                    'is_required' => $question->is_required,
                    'answer_data_keys' => array_keys($answerData),
                    'has_file_in_answer_data' => isset($answerData['file']),
                    'file_info' => isset($answerData['file']) ? $this->getFileInfo($answerData['file']) : 'no_file',
                    'all_request_files' => array_keys($request->allFiles()),
                    'request_has_files' => $request->hasAnyFile()
                ]);

                // Check for file in multiple possible locations
                $uploadedFile = null;

                // Method 1: Direct file in answerData
                if (isset($answerData['file']) && $answerData['file'] instanceof \Illuminate\Http\UploadedFile) {
                    $uploadedFile = $answerData['file'];
                    \Illuminate\Support\Facades\Log::info('File found in answerData');
                }

                // Method 2: File in request with question UUID key
                if (!$uploadedFile) {
                    $fileKey = "responses.{$question->uuid}.file";
                    if ($request->hasFile($fileKey)) {
                        $uploadedFile = $request->file($fileKey);
                        \Illuminate\Support\Facades\Log::info('File found with fileKey', ['key' => $fileKey]);
                    }
                }

                // Method 3: Look through all files in request for matching pattern
                if (!$uploadedFile) {
                    $allFiles = $request->allFiles();
                    foreach ($allFiles as $key => $file) {
                        if (strpos($key, $question->uuid) !== false && $file instanceof \Illuminate\Http\UploadedFile) {
                            $uploadedFile = $file;
                            \Illuminate\Support\Facades\Log::info('File found via pattern matching', ['key' => $key]);
                            break;
                        }
                    }
                }

                if ($uploadedFile && $uploadedFile instanceof \Illuminate\Http\UploadedFile) {
                    \Illuminate\Support\Facades\Log::info('File found, processing upload', [
                        'original_name' => $uploadedFile->getClientOriginalName(),
                        'size' => $uploadedFile->getSize()
                    ]);

                    $fileData = $this->handleFileUpload($uploadedFile, $survey, $question);
                    $answer->file_url = $fileData['url'];
                    $answer->answer_data = $fileData['metadata'];
                    $answer->answer_text = $fileData['filename'];
                } else {
                    // If it's a required question and no file uploaded
                    if ($question->is_required) {
                        \Illuminate\Support\Facades\Log::error('Required file upload missing', [
                            'question_uuid' => $question->uuid,
                            'question_title' => $question->title,
                            'request_files' => array_keys($request->allFiles()),
                            'answer_data' => $answerData,
                            'request_input_keys' => array_keys($request->all())
                        ]);
                        throw new \Exception("File is required for question: {$question->title}");
                    }
                    // Skip saving answer for optional file fields with no file
                    \Illuminate\Support\Facades\Log::info('Optional file field skipped (no file uploaded)', [
                        'question_uuid' => $question->uuid
                    ]);
                    return null;
                }
                break;

            default:
                $answer->answer_text = $answerData['value'] ?? '';
                $answer->answer_data = $answerData;
        }

        $answer->save();
        return $answer;
    }

    private function handleFileUpload(\Illuminate\Http\UploadedFile $file, Survey $survey, Question $question): array
    {
        try {
            // Generate unique filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            $path = "surveys/{$survey->uuid}/questions/{$question->uuid}/{$filename}";

            \Illuminate\Support\Facades\Log::info('Starting file upload', [
                'original_name' => $originalName,
                'filename' => $filename,
                'path' => $path,
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType()
            ]);

            // Store file in public storage (local)
            $stored = Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

            if (!$stored) {
                throw new \Exception('Failed to store file locally');
            }

            // Generate public URL for local storage
            $publicUrl = asset('storage/' . $path);

            \Illuminate\Support\Facades\Log::info('File upload successful', [
                'path' => $path,
                'url' => $publicUrl,
                'stored' => $stored
            ]);

            return [
                'url' => $publicUrl,
                'filename' => $originalName,
                'metadata' => [
                    'original_filename' => $originalName,
                    'stored_filename' => $filename,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'path' => $path,
                    'uploaded_at' => now()->toISOString()
                ]
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('File upload failed', [
                'error' => $e->getMessage(),
                'file' => $file->getClientOriginalName(),
                'survey_uuid' => $survey->uuid,
                'question_uuid' => $question->uuid
            ]);
            throw $e;
        }
    }

    private function getFileInfo($file): string
    {
        if (is_object($file)) {
            return get_class($file);
        } elseif (is_string($file)) {
            return 'string: ' . $file;
        } elseif (is_array($file)) {
            return 'array: ' . json_encode($file);
        }
        return gettype($file) . ': ' . (string)$file;
    }
}

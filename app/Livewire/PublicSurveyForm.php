<?php

namespace App\Livewire;

use App\Models\Survey;
use App\Models\Question;
use App\Services\SurveyService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PublicSurveyForm extends Component
{
    use WithFileUploads;

    public $surveyUuid;
    public $survey = null;
    public $questions = [];
    public $responses = [];
    public $currentStep = 1;
    public $totalSteps = 1;
    public $loading = true;
    public $submitting = false;
    public $submitted = false;
    public $validationErrors = [];

    // File uploads
    public $uploadedFiles = [];

    protected SurveyService $surveyService;

    protected $rules = [
        'responses.*' => 'nullable',
        'uploadedFiles.*' => 'nullable|file|max:10240', // 10MB max
    ];

    public function boot(SurveyService $surveyService): void
    {
        $this->surveyService = $surveyService;
    }

    public function mount($uuid)
    {
        $this->surveyUuid = $uuid;
        $this->loadSurvey();
    }

    public function render()
    {
        return view('livewire.public-survey-form')->layout('layouts.public');
    }

    public function loadSurvey()
    {
        $this->loading = true;
        $this->validationErrors = [];

        try {
            // Use SurveyService directly instead of HTTP API calls
            $survey = $this->surveyService->findByUuid($this->surveyUuid);

            if ($survey) {
                // Convert survey model to array format for consistency
                $this->survey = [
                    'uuid' => $survey->uuid,
                    'name' => $survey->name,
                    'description' => $survey->description,
                    'status' => $survey->status,
                    'is_public' => $survey->is_public,
                    'questions' => $survey->questions->map(function($question) {
                        return [
                            'uuid' => $question->uuid,
                            'name' => $question->name,
                            'question_text' => $question->question_text,
                            'is_required' => $question->is_required,
                            'options' => $question->options,
                            'question_type' => [
                                'name' => $question->questionType->name ?? 'text',
                                'display_name' => $question->questionType->display_name ?? 'Text'
                            ]
                        ];
                    })->toArray()
                ];

                $this->questions = $this->survey['questions'] ?? [];

                // Force single page view - no steps
                $this->totalSteps = 1;
                $this->currentStep = 1;

                // Initialize responses array
                foreach ($this->questions as $question) {
                    if (!isset($this->responses[$question['uuid']])) {
                        $this->responses[$question['uuid']] = $this->getDefaultValue($question);
                    }
                }

                // Check if survey allows responses
                if ($this->survey['status'] !== 'active') {
                    $this->validationErrors['survey'] = 'This survey is not currently accepting responses.';
                }

                Log::info('Public survey loaded successfully', [
                    'survey_uuid' => $this->surveyUuid,
                    'survey_name' => $survey->name,
                    'question_count' => count($this->questions),
                    'survey_status' => $survey->status
                ]);

            } else {
                $this->validationErrors['survey'] = 'Survey not found or is not accessible.';
            }
        } catch (\Exception $e) {
            Log::error('Error loading public survey: ' . $e->getMessage(), [
                'survey_uuid' => $this->surveyUuid,
                'trace' => $e->getTraceAsString()
            ]);
            $this->validationErrors['survey'] = 'Error loading survey. Please try again later.';
        }

        $this->loading = false;
    }

    public function submitResponse()
    {
        $this->submitting = true;
        $this->validationErrors = [];

        Log::info('Starting survey submission', [
            'survey_uuid' => $this->surveyUuid,
            'total_questions' => count($this->questions),
            'current_responses' => array_keys($this->responses),
            'uploaded_files' => array_keys($this->uploadedFiles)
        ]);

        // Validate required questions
        $hasErrors = false;
        foreach ($this->questions as $question) {
            if ($question['is_required']) {
                $questionUuid = $question['uuid'];
                $response = $this->responses[$questionUuid] ?? null;
                $questionType = $question['question_type']['name'] ?? 'text';

                $isEmpty = false;

                // Check if the field is empty based on question type
                switch ($questionType) {
                    case 'checkbox':
                        $isEmpty = empty($response) || (is_array($response) && empty(array_filter($response)));
                        break;
                    case 'file-upload':
                    case 'file':
                    case 'attachment':
                        $isEmpty = empty($this->uploadedFiles[$questionUuid] ?? null) &&
                                  ($response === null || $response === '' || $response === 'file_uploaded');
                        break;
                    case 'rating':
                        $isEmpty = $response === null || $response === '' || $response == 0;
                        break;
                    case 'number':
                        $isEmpty = $response === null || $response === '';
                        break;
                    case 'multiple-choice':
                    case 'multiple_choice':
                    case 'radio':
                    case 'dropdown':
                    case 'select':
                        $isEmpty = empty($response) || (is_string($response) && trim($response) === '');
                        break;
                    default:
                        $isEmpty = empty($response) || (is_string($response) && trim($response) === '');
                        break;
                }

                if ($isEmpty) {
                    $this->validationErrors["responses.{$questionUuid}"] = "This field is required.";
                    $hasErrors = true;
                    Log::warning('Required field empty', [
                        'question_uuid' => $questionUuid,
                        'question_type' => $questionType,
                        'response' => $response
                    ]);
                }
            }
        }

        if ($hasErrors) {
            $this->submitting = false;
            Log::warning('Survey submission failed validation', [
                'validation_errors' => $this->validationErrors,
                'total_errors' => count($this->validationErrors)
            ]);
            $this->dispatch('toast', message: 'Please fill in all required fields.', type: 'error');
            return;
        }

        try {
            // Use direct database operations instead of HTTP API calls
            $survey = Survey::where('uuid', $this->surveyUuid)->first();

            if (!$survey) {
                throw new \Exception('Survey not found');
            }

            // Generate a unique respondent ID for this submission
            $respondentId = Str::uuid();

            // Process each response and save directly to a database
            $savedAnswers = [];

            \Illuminate\Support\Facades\DB::transaction(function () use ($survey, $respondentId, &$savedAnswers) {
                foreach ($this->responses as $questionUuid => $response) {
                    // Skip empty responses
                    if ($this->isResponseEmpty($response, 'text') && !isset($this->uploadedFiles[$questionUuid])) {
                        continue;
                    }

                    $question = Question::where('uuid', $questionUuid)->first();
                    if (!$question) {
                        Log::warning('Question not found for UUID', ['uuid' => $questionUuid]);
                        continue;
                    }

                    $questionType = $question->questionType->name ?? 'text';

                    // Handle file uploads
                    if (in_array($questionType, ['file-upload', 'file', 'attachment']) &&
                        isset($this->uploadedFiles[$questionUuid]) &&
                        $this->uploadedFiles[$questionUuid]) {

                        $file = $this->uploadedFiles[$questionUuid];
                        $filename = time() . '_' . $file->getClientOriginalName();
                        $path = $file->storeAs('survey-responses', $filename, 'public');

                        // Create answer record for file using correct field names
                        $answer = \App\Models\Answer::create([
                            'survey_id' => $survey->id,
                            'question_id' => $question->id,
                            'respondent_id' => $respondentId,
                            'respondent_type' => 'anonymous',
                            'answer_text' => $filename,
                            'answer_data' => json_encode(['file_path' => $path, 'original_name' => $file->getClientOriginalName()]),
                            'file_url' => $path,
                            'ip_address' => request()->ip(),
                            'user_agent' => request()->userAgent(),
                            'submitted_at' => now(),
                        ]);

                        $savedAnswers[] = $answer;
                        continue;
                    }

                    // Handle different question types
                    switch ($questionType) {
                        case 'checkbox':
                            if (is_array($response) && !empty($response)) {
                                $selectedOptions = implode(',', $response);
                                $answer = \App\Models\Answer::create([
                                    'survey_id' => $survey->id,
                                    'question_id' => $question->id,
                                    'respondent_id' => $respondentId,
                                    'respondent_type' => 'anonymous',
                                    'answer_text' => $selectedOptions,
                                    'answer_data' => json_encode(['selected_options' => $response]),
                                    'ip_address' => request()->ip(),
                                    'user_agent' => request()->userAgent(),
                                    'submitted_at' => now(),
                                ]);
                                $savedAnswers[] = $answer;
                            }
                            break;

                        case 'multiple_choice':
                        case 'multiple-choice':
                        case 'radio':
                        case 'dropdown':
                        case 'select':
                            if (!empty($response)) {
                                $optionIndex = null;
                                if (!empty($question->options)) {
                                    $optionIndex = array_search($response, $question->options);
                                }

                                $answer = \App\Models\Answer::create([
                                    'survey_id' => $survey->id,
                                    'question_id' => $question->id,
                                    'respondent_id' => $respondentId,
                                    'respondent_type' => 'anonymous',
                                    'answer_text' => $response,
                                    'answer_data' => json_encode([
                                        'selected_option' => $response,
                                        'option_index' => $optionIndex !== false ? $optionIndex : null
                                    ]),
                                    'ip_address' => request()->ip(),
                                    'user_agent' => request()->userAgent(),
                                    'submitted_at' => now(),
                                ]);
                                $savedAnswers[] = $answer;
                            }
                            break;

                        case 'rating':
                        case 'number':
                            if ($response !== null && $response !== '') {
                                $answer = \App\Models\Answer::create([
                                    'survey_id' => $survey->id,
                                    'question_id' => $question->id,
                                    'respondent_id' => $respondentId,
                                    'respondent_type' => 'anonymous',
                                    'answer_text' => (string) $response,
                                    'answer_data' => json_encode([
                                        'numeric_value' => is_numeric($response) ? (float) $response : null,
                                        'question_type' => $questionType
                                    ]),
                                    'ip_address' => request()->ip(),
                                    'user_agent' => request()->userAgent(),
                                    'submitted_at' => now(),
                                ]);
                                $savedAnswers[] = $answer;
                            }
                            break;

                        default: // text, textarea, email, url, yes-no, etc.
                            if (!empty($response)) {
                                $answer = \App\Models\Answer::create([
                                    'survey_id' => $survey->id,
                                    'question_id' => $question->id,
                                    'respondent_id' => $respondentId,
                                    'respondent_type' => 'anonymous',
                                    'answer_text' => (string) $response,
                                    'answer_data' => json_encode(['question_type' => $questionType]),
                                    'ip_address' => request()->ip(),
                                    'user_agent' => request()->userAgent(),
                                    'submitted_at' => now(),
                                ]);
                                $savedAnswers[] = $answer;
                            }
                            break;
                    }
                }
            });

            // Mark as successfully submitted
            $this->submitted = true;
            $this->dispatch('survey-submitted');
            $this->dispatch('toast', message: 'Survey submitted successfully!', type: 'success');

            Log::info('Survey submitted successfully via database', [
                'survey_uuid' => $this->surveyUuid,
                'respondent_id' => $respondentId,
                'answers_saved' => count($savedAnswers),
                'total_questions' => count($this->questions)
            ]);

        } catch (\Exception $e) {
            Log::error('Survey submission exception', [
                'exception' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'survey_uuid' => $this->surveyUuid,
                'trace' => $e->getTraceAsString()
            ]);

            $this->validationErrors['submission'] = 'An error occurred while submitting your response. Please try again.';
            $this->dispatch('toast', message: 'An error occurred. Please try again.', type: 'error');
        }

        $this->submitting = false;
    }

    // Remove step navigation methods
    public function nextStep() {}
    public function previousStep() {}
    public function goToStep($step) {}

    // Add method to handle real-time validation clearing
    public function updatedResponses($value, $key)
    {
        // Clear validation error for this specific field when updated
        unset($this->validationErrors["responses.{$key}"]);

        // If there are no more validation errors, clear the submission error too
        $responseErrors = array_filter($this->validationErrors, function($errorKey) {
            return strpos($errorKey, 'responses.') === 0;
        }, ARRAY_FILTER_USE_KEY);

        if (empty($responseErrors)) {
            unset($this->validationErrors['submission']);
        }
    }

    private function getDefaultValue($question)
    {
        $questionType = $question['question_type']['name'] ?? 'text';

        switch ($questionType) {
            case 'checkbox':
                return [];
            case 'rating':
                return null; // Changed from 0 to null for proper validation
            case 'number':
                return null; // Changed from 0 to null for proper validation
            case 'file':
            case 'attachment':
                return null;
            default:
                return '';
        }
    }

    private function formatResponseForQuestion($question, $response)
    {
        $questionType = $question['question_type']['name'] ?? 'text';

        // Don't format empty responses (except for valid zeros in numbers/ratings)
        if ($this->isResponseEmpty($response, $questionType)) {
            return null;
        }

        switch ($questionType) {
            case 'text':
            case 'textarea':
            case 'email':
            case 'url':
                return ['value' => (string) $response];

            case 'number':
            case 'rating':
                return ['value' => (string) $response];

            case 'multiple_choice':
            case 'multiple-choice':
            case 'radio':
            case 'dropdown':
            case 'select':
                $optionIndex = null;
                if (!empty($question['options'])) {
                    $optionIndex = array_search($response, $question['options']);
                }
                return [
                    'value' => $response,
                    'option_index' => $optionIndex !== false ? $optionIndex : null
                ];

            case 'checkbox':
                $optionIndices = [];
                if (!empty($question['options']) && is_array($response)) {
                    foreach ($response as $selectedOption) {
                        $index = array_search($selectedOption, $question['options']);
                        if ($index !== false) {
                            $optionIndices[] = $index;
                        }
                    }
                }
                return [
                    'values' => is_array($response) ? $response : [],
                    'option_indices' => $optionIndices
                ];

            case 'file-upload':
            case 'file':
            case 'attachment':
                if (isset($this->uploadedFiles[$question['uuid']])) {
                    Log::info('Preparing file for upload', [
                        'question_uuid' => $question['uuid'],
                        'filename' => $this->uploadedFiles[$question['uuid']]->getClientOriginalName(),
                        'size' => $this->uploadedFiles[$question['uuid']]->getSize()
                    ]);
                    return ['file' => $this->uploadedFiles[$question['uuid']]];
                }
                return null;

            case 'yes-no':
                return ['value' => (string) $response];

            default:
                return ['value' => (string) $response];
        }
    }

    private function isResponseEmpty($response, $questionType)
    {
        switch ($questionType) {
            case 'checkbox':
                return empty($response) || (is_array($response) && empty(array_filter($response)));
            case 'rating':
            case 'number':
                return $response === null || $response === '';
            case 'file':
            case 'attachment':
                return empty($response);
            default:
                return empty($response) || (is_string($response) && trim($response) === '');
        }
    }

    public function updatedUploadedFiles($value, $key)
    {
        // Clear any previous validation errors
        unset($this->validationErrors["responses.{$key}"]);
        unset($this->validationErrors["uploadedFiles.{$key}"]);

        if ($value) {
            // Set the response value to indicate a file was uploaded
            $this->responses[$key] = 'file_uploaded';

            // Validate file upload
            $this->validateOnly("uploadedFiles.{$key}");

            Log::info('File uploaded successfully', [
                'question_uuid' => $key,
                'filename' => $value->getClientOriginalName(),
                'size' => $value->getSize()
            ]);
        }
    }

    public function removeFile($questionUuid)
    {
        unset($this->uploadedFiles[$questionUuid]);
        $this->responses[$questionUuid] = null;

        // Clear validation errors
        unset($this->validationErrors["responses.{$questionUuid}"]);
        unset($this->validationErrors["uploadedFiles.{$questionUuid}"]);

        Log::info('File removed', [
            'question_uuid' => $questionUuid
        ]);
    }

    private function getCurrentStepQuestion()
    {
        if ($this->totalSteps > 1 && isset($this->questions[$this->currentStep - 1])) {
            return $this->questions[$this->currentStep - 1];
        }
        return null;
    }
}

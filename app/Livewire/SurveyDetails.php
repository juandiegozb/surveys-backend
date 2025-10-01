<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\SurveyService;
use App\Services\QuestionService;
use App\Models\QuestionType;
use Illuminate\Support\Facades\Log;

class SurveyDetails extends Component
{
    public $surveyUuid;
    public $survey = null;
    public $questions = [];
    public $loading = false;

    // Question creation modal properties
    public $showCreateQuestionModal = false;
    public $questionName = '';
    public $questionText = '';
    public $questionTypeId = 1; // Default to text
    public $options = [''];
    public $isRequired = false;
    public $questionTypes = [];

    // Question linking modal properties
    public $showLinkQuestionsModal = false;
    public $availableQuestions = [];
    public $selectedQuestionUuids = [];
    public $searchAvailable = '';
    public $filterQuestionType = '';
    public $loadingAvailable = false;

    // Response viewing properties
    public $showResponsesModal = false;
    public $responses = [];
    public $responsesByRespondent = [];
    public $loadingResponses = false;
    public $analytics = [];
    public $currentResponsePage = 1;

    // Survey editing properties
    public $showEditSurveyModal = false;
    public $editingSurveyName = '';
    public $editingSurveyDescription = '';
    public $editingSurveyStatus = '';

    protected SurveyService $surveyService;
    protected QuestionService $questionService;

    protected $rules = [
        'questionName' => 'required|string|max:255',
        'questionText' => 'required|string|max:2000',
        'questionTypeId' => 'required|integer',
        'options' => 'nullable|array',
        'options.*' => 'nullable|string|max:255',
        'isRequired' => 'boolean',
    ];

    public function boot(SurveyService $surveyService, QuestionService $questionService): void
    {
        $this->surveyService = $surveyService;
        $this->questionService = $questionService;
    }

    public function mount($uuid)
    {
        $this->surveyUuid = $uuid;
        $this->loadSurveyDetails();
        $this->loadQuestionTypes();
    }

    public function render()
    {
        return view('livewire.survey-details')->layout('layouts.app');
    }

    public function loadSurveyDetails(): void
    {
        $this->loading = true;

        try {
            // Use SurveyService directly instead of HTTP calls
            $this->survey = $this->surveyService->findByUuid($this->surveyUuid);

            // Load questions for this survey
            $this->questions = $this->survey->questions ?? [];

            Log::info('Survey details loaded successfully', [
                'survey_uuid' => $this->surveyUuid,
                'survey_name' => $this->survey->name ?? 'Unknown',
                'question_count' => count($this->questions)
            ]);

        } catch (\Exception $e) {
            $this->survey = null;
            $this->questions = [];

            Log::error('Error loading survey details', [
                'survey_uuid' => $this->surveyUuid,
                'error' => $e->getMessage()
            ]);

            $this->dispatch('toast', message: 'Error loading survey: ' . $e->getMessage(), type: 'error');
        }

        $this->loading = false;
    }

    public function loadQuestionTypes(): void
    {
        try {
            $this->questionTypes = QuestionType::all();
        } catch (\Exception $e) {
            $this->questionTypes = [];
            Log::error('Error loading question types', ['error' => $e->getMessage()]);
        }
    }

    public function createQuestion()
    {
        $this->validate();

        try {
            // Filter out empty options
            $filteredOptions = array_filter($this->options, function($option) {
                return !empty(trim($option));
            });

            Log::info('Creating question for survey', [
                'survey_uuid' => $this->surveyUuid,
                'question_name' => $this->questionName,
            ]);

            $question = $this->questionService->create([
                'name' => $this->questionName,
                'question_text' => $this->questionText,
                'question_type_id' => $this->questionTypeId,
                'options' => !empty($filteredOptions) ? array_values($filteredOptions) : null,
                'is_required' => $this->isRequired,
                'survey_uuid' => $this->surveyUuid, // Link to survey
            ]);

            $this->dispatch('toast', message: 'Question created successfully!', type: 'success');
            $this->resetQuestionForm();
            $this->showCreateQuestionModal = false;

            // Refresh survey details to show new question
            $this->loadSurveyDetails();

            Log::info('Question created and survey reloaded', [
                'survey_uuid' => $this->surveyUuid,
                'questions_count' => count($this->questions)
            ]);

        } catch (\Exception $e) {
            Log::error('Exception creating question: ' . $e->getMessage(), [
                'survey_uuid' => $this->surveyUuid,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('toast', message: 'Error creating question: ' . $e->getMessage(), type: 'error');
        }
    }

    public function addOption()
    {
        $this->options[] = '';
    }

    public function removeOption($index)
    {
        if (count($this->options) > 1) {
            unset($this->options[$index]);
            $this->options = array_values($this->options);
        }
    }

    public function resetQuestionForm()
    {
        $this->questionName = '';
        $this->questionText = '';
        $this->questionTypeId = 1;
        $this->options = [''];
        $this->isRequired = false;
    }

    public function closeModals()
    {
        $this->showCreateQuestionModal = false;
        $this->showLinkQuestionsModal = false;
        $this->resetQuestionForm();
        $this->resetLinkForm();
    }

    // Methods for linking existing questions
    public function openLinkQuestionsModal()
    {
        $this->showLinkQuestionsModal = true;
        $this->loadAvailableQuestions();
    }

    public function loadAvailableQuestions()
    {
        $this->loadingAvailable = true;

        try {
            // Use direct Eloquent queries instead of HTTP API to avoid routing issues
            $survey = \App\Models\Survey::where('uuid', $this->surveyUuid)->first();

            if (!$survey) {
                $this->dispatch('toast', message: 'Survey not found', type: 'error');
                $this->availableQuestions = [];
                $this->loadingAvailable = false;
                return;
            }

            // Get IDs of questions already assigned to this survey
            $assignedQuestionIds = $survey->questions()->pluck('questions.id')->toArray();

            // Build query for available questions
            $query = \App\Models\Question::with('questionType')
                ->where('is_active', true)
                ->whereNotIn('id', $assignedQuestionIds);

            // Apply search filter
            if ($this->searchAvailable) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->searchAvailable . '%')
                      ->orWhere('question_text', 'like', '%' . $this->searchAvailable . '%');
                });
            }

            // Apply question type filter
            if ($this->filterQuestionType) {
                $query->where('question_type_id', $this->filterQuestionType);
            }

            $questions = $query->orderBy('created_at', 'desc')->get();

            // Convert to array format similar to API response
            $this->availableQuestions = $questions->map(function($question) {
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
            })->toArray();

            Log::info('Available questions loaded directly from database', [
                'survey_uuid' => $this->surveyUuid,
                'count' => count($this->availableQuestions),
                'assigned_questions' => count($assignedQuestionIds)
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading available questions: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Error loading available questions: ' . $e->getMessage(), type: 'error');
            $this->availableQuestions = [];
        }

        $this->loadingAvailable = false;
    }

    public function updatedSearchAvailable()
    {
        $this->loadAvailableQuestions();
    }

    public function updatedFilterQuestionType()
    {
        $this->loadAvailableQuestions();
    }

    public function toggleQuestionSelection($questionUuid)
    {
        if (in_array($questionUuid, $this->selectedQuestionUuids)) {
            $this->selectedQuestionUuids = array_values(array_diff($this->selectedQuestionUuids, [$questionUuid]));
        } else {
            $this->selectedQuestionUuids[] = $questionUuid;
        }
    }

    public function selectAllQuestions()
    {
        $this->selectedQuestionUuids = collect($this->availableQuestions)->pluck('uuid')->toArray();
    }

    public function deselectAllQuestions()
    {
        $this->selectedQuestionUuids = [];
    }

    public function linkSelectedQuestions()
    {
        if (empty($this->selectedQuestionUuids)) {
            $this->dispatch('toast', message: 'Please select at least one question to link', type: 'warning');
            return;
        }

        try {
            // Use direct Eloquent operations instead of HTTP API
            $survey = \App\Models\Survey::where('uuid', $this->surveyUuid)->first();
            $questions = \App\Models\Question::whereIn('uuid', $this->selectedQuestionUuids)->get();

            if (!$survey) {
                $this->dispatch('toast', message: 'Survey not found', type: 'error');
                return;
            }

            if ($questions->count() !== count($this->selectedQuestionUuids)) {
                $this->dispatch('toast', message: 'Some questions not found', type: 'error');
                return;
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($survey, $questions) {
                foreach ($questions as $question) {
                    // Check if already linked to avoid duplicates
                    if (!$survey->questions()->where('question_id', $question->id)->exists()) {
                        // Get the next order number
                        $maxOrder = $survey->questions()->max('survey_questions.order') ?? 0;

                        // Attach question to survey
                        $survey->questions()->attach($question->id, [
                            'order' => $maxOrder + 1,
                            'is_active' => true,
                            'survey_specific_settings' => json_encode([])
                        ]);

                        // Increment question usage count
                        $question->increment('usage_count');
                    }
                }

                // Update survey question count
                $survey->question_count = $survey->questions()->count();
                $survey->save();

                // Clear survey cache
                $survey->clearCache();
            });

            $this->dispatch('toast', message: count($this->selectedQuestionUuids) . ' questions linked successfully!', type: 'success');
            $this->showLinkQuestionsModal = false;
            $this->resetLinkForm();
            $this->loadSurveyDetails(); // Reload the survey to show new questions

            Log::info('Questions linked successfully via Eloquent', [
                'survey_uuid' => $this->surveyUuid,
                'questions_count' => count($this->selectedQuestionUuids)
            ]);

        } catch (\Exception $e) {
            Log::error('Error linking questions via Eloquent: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Error linking questions: ' . $e->getMessage(), type: 'error');
        }
    }

    public function resetLinkForm()
    {
        $this->selectedQuestionUuids = [];
        $this->searchAvailable = '';
        $this->filterQuestionType = '';
        $this->availableQuestions = [];
    }

    // Method for removing a question from survey
    public function removeQuestionFromSurvey($questionUuid)
    {
        try {
            $survey = \App\Models\Survey::where('uuid', $this->surveyUuid)->first();
            $question = \App\Models\Question::where('uuid', $questionUuid)->first();

            if (!$survey || !$question) {
                $this->dispatch('toast', message: 'Survey or question not found', type: 'error');
                return;
            }

            \Illuminate\Support\Facades\DB::transaction(function () use ($survey, $question) {
                // Detach the question from the survey
                $survey->questions()->detach($question->id);

                // Decrement question usage count
                if ($question->usage_count > 0) {
                    $question->decrement('usage_count');
                }

                // Update survey question count
                $survey->question_count = $survey->questions()->count();
                $survey->save();

                // Clear survey cache
                $survey->clearCache();
            });

            // Reset component state to force full re-render
            $this->reset(['questions', 'survey']);

            // Reload everything to ensure fresh state
            $this->loadSurveyDetails();
            $this->loadQuestions();

            $this->dispatch('toast', message: 'Question removed from survey successfully!', type: 'success');

            Log::info('Question removed from survey successfully', [
                'survey_uuid' => $this->surveyUuid,
                'question_uuid' => $questionUuid,
                'remaining_questions' => $survey->fresh()->questions()->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error removing question from survey: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Error removing question: ' . $e->getMessage(), type: 'error');
        }
    }

    public function loadQuestions()
    {
        if ($this->survey && isset($this->survey['questions'])) {
            $this->questions = $this->survey['questions'];
        }
    }

    // Methods for viewing responses
    public function openResponsesModal()
    {
        $this->showResponsesModal = true;
        $this->loadResponses();
    }

    public function loadResponses()
    {
        $this->loadingResponses = true;

        try {
            // Use direct Eloquent queries instead of HTTP API
            $survey = \App\Models\Survey::where('uuid', $this->surveyUuid)->first();

            if (!$survey) {
                $this->dispatch('toast', message: 'Survey not found', type: 'error');
                $this->responses = [];
                $this->loadingResponses = false;
                return;
            }

            // Load responses directly from the database
            $responses = \App\Models\Answer::with(['question'])
                ->whereHas('question', function($query) use ($survey) {
                    $query->whereHas('surveys', function($surveyQuery) use ($survey) {
                        $surveyQuery->where('surveys.id', $survey->id);
                    });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Convert to array format using correct Answer model field names
            $this->responses = $responses->map(function($answer) {
                // Parse answer_data JSON if it exists
                $answerData = $answer->answer_data ? json_decode($answer->answer_data, true) : [];

                // Check if this response has file attachments
                $hasAttachments = !empty($answer->file_url) || !empty($answer->attachments);

                return [
                    'id' => $answer->id,
                    'question_id' => $answer->question_id,
                    'question_text' => $answer->question->question_text ?? '',
                    'answer_text' => $answer->answer_text ?? '',
                    'text_response' => $answer->answer_text ?? '', // For backward compatibility with blade view
                    'selected_option' => $answerData['selected_option'] ?? $answer->answer_text ?? '',
                    'numeric_value' => $answerData['numeric_value'] ?? null,
                    'file_path' => $answer->file_url ?? null,
                    'file_url' => $answer->file_url ?? null,
                    'attachments' => $answer->attachments ?? [],
                    'has_attachments' => $hasAttachments,
                    'respondent_id' => $answer->respondent_id,
                    'respondent_type' => $answer->respondent_type ?? 'anonymous',
                    'ip_address' => $answer->ip_address,
                    'user_agent' => $answer->user_agent ?? '',
                    'metadata' => $answer->metadata ?? [],
                    'created_at' => $answer->created_at->toISOString(),
                    'submitted_at' => $answer->submitted_at ? $answer->submitted_at->toISOString() : $answer->created_at->toISOString(),
                    'answer_data' => $answerData, // Include full structured data
                ];
            })->toArray();

            $this->organizeResponsesByRespondent();

            Log::info('Responses loaded directly from database', [
                'survey_uuid' => $this->surveyUuid,
                'response_count' => count($this->responses)
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading responses: ' . $e->getMessage());
            $this->dispatch('toast', message: 'Error loading responses: ' . $e->getMessage(), type: 'error');
            $this->responses = [];
        }

        $this->loadingResponses = false;
    }

    public function organizeResponsesByRespondent()
    {
        $this->responsesByRespondent = [];

        foreach ($this->responses as $response) {
            $respondentId = $response['respondent_id'] ?? 'unknown';
            if (!isset($this->responsesByRespondent[$respondentId])) {
                $this->responsesByRespondent[$respondentId] = [];
            }
            $this->responsesByRespondent[$respondentId][] = $response;
        }
    }

    public function closeResponsesModal()
    {
        $this->showResponsesModal = false;
        $this->responses = [];
        $this->responsesByRespondent = [];
    }

    public function viewResponseAnalytics($respondentId)
    {
        // Filter responses for the selected respondent
        $filteredResponses = collect($this->responses)->where('respondent_id', $respondentId);

        // Generate analytics data
        $this->analytics = [
            'total_responses' => $filteredResponses->count(),
            'question_breakdown' => $filteredResponses->groupBy('question_id')->map(function ($group) {
                return [
                    'question_text' => $group->first()['question_text'] ?? '',
                    'response_count' => $group->count(),
                    'options' => $group->pluck('selected_option')->unique(),
                ];
            }),
        ];
    }

    public function clearAnalytics()
    {
        $this->analytics = [];
    }

    public function loadAnalytics()
    {
        try {
            // Use direct Eloquent queries instead of HTTP API
            $survey = \App\Models\Survey::where('uuid', $this->surveyUuid)->first();

            if (!$survey) {
                $this->analytics = [];
                return;
            }

            // Load analytics data directly from database
            $responses = \App\Models\Answer::with(['question'])
                ->whereHas('question', function($query) use ($survey) {
                    $query->whereHas('surveys', function($surveyQuery) use ($survey) {
                        $surveyQuery->where('surveys.id', $survey->id);
                    });
                })
                ->get();

            // Generate analytics similar to API response
            $totalResponses = $responses->count();
            $uniqueRespondents = $responses->pluck('respondent_id')->unique()->count();

            $questionBreakdown = $responses->groupBy('question_id')->map(function($questionResponses) {
                $question = $questionResponses->first()->question;
                return [
                    'question_text' => $question->question_text ?? '',
                    'question_type' => $question->questionType->name ?? 'text',
                    'response_count' => $questionResponses->count(),
                    'unique_responses' => $questionResponses->pluck('text_response')->filter()->unique()->count(),
                    'most_common_option' => $questionResponses->pluck('selected_option')->filter()->mode()->first(),
                ];
            });

            // Calculate response rate separately to avoid type issues
            $responseRate = 0.0;
            if ($uniqueRespondents > 0) {
                $responseRate = (float) number_format((float)$totalResponses / (float)$uniqueRespondents, 2, '.', '');
            }

            $this->analytics = [
                'total_responses' => $totalResponses,
                'unique_respondents' => $uniqueRespondents,
                'question_breakdown' => $questionBreakdown->toArray(),
                'response_rate' => $responseRate
            ];

            Log::info('Analytics loaded directly from database', [
                'survey_uuid' => $this->surveyUuid,
                'total_responses' => $totalResponses,
                'unique_respondents' => $uniqueRespondents
            ]);

        } catch (\Exception $e) {
            Log::error('Error loading analytics: ' . $e->getMessage());
            $this->analytics = [];
        }
    }

    public function getPublicUrl()
    {
        return route('survey.public', ['uuid' => $this->surveyUuid]);
    }

    public function getShortUrl()
    {
        return route('survey.short', ['uuid' => $this->surveyUuid]);
    }

    public function copyToClipboard($url)
    {
        $this->dispatch('copy-to-clipboard', url: $url);
        $this->dispatch('toast', message: 'URL copied to clipboard!', type: 'success');
    }

    public function editSurvey()
    {
        if (!$this->survey) {
            $this->dispatch('toast', message: 'Survey not found', type: 'error');
            return;
        }

        // Populate the editing fields with current survey data
        $this->editingSurveyName = $this->survey->name ?? '';
        $this->editingSurveyDescription = $this->survey->description ?? '';
        $this->editingSurveyStatus = $this->survey->status ?? 'draft';

        // Show the edit modal
        $this->showEditSurveyModal = true;
    }

    public function updateSurvey()
    {
        // Validate the editing fields
        $this->validate([
            'editingSurveyName' => 'required|string|max:255',
            'editingSurveyDescription' => 'nullable|string|max:2000',
            'editingSurveyStatus' => 'required|in:draft,active,inactive,completed'
        ]);

        try {
            $updatedData = [
                'name' => $this->editingSurveyName,
                'description' => $this->editingSurveyDescription,
                'status' => $this->editingSurveyStatus,
            ];

            // Update the survey using the service
            $this->survey = $this->surveyService->update($this->surveyUuid, $updatedData);

            $this->dispatch('toast', message: 'Survey updated successfully!', type: 'success');
            $this->closeEditSurveyModal();

            Log::info('Survey updated successfully', [
                'survey_uuid' => $this->surveyUuid,
                'updated_fields' => array_keys($updatedData)
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating survey: ' . $e->getMessage(), [
                'survey_uuid' => $this->surveyUuid,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('toast', message: 'Error updating survey: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeEditSurveyModal()
    {
        $this->showEditSurveyModal = false;
        $this->editingSurveyName = '';
        $this->editingSurveyDescription = '';
        $this->editingSurveyStatus = '';
    }

    public function deleteSurvey()
    {
        try {
            // Delete the survey using the service
            $this->surveyService->delete($this->surveyUuid);

            $this->dispatch('toast', message: 'Survey deleted successfully!', type: 'success');

            Log::info('Survey deleted successfully', [
                'survey_uuid' => $this->surveyUuid
            ]);

            // Redirect to surveys list
            return $this->redirect('/surveys');

        } catch (\Exception $e) {
            Log::error('Error deleting survey: ' . $e->getMessage(), [
                'survey_uuid' => $this->surveyUuid,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('toast', message: 'Error deleting survey: ' . $e->getMessage(), type: 'error');
            return null;
        }
    }
}

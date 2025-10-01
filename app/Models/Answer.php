<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Answer extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'survey_id',
        'question_id',
        'respondent_id',
        'respondent_type',
        'answer_text',
        'answer_data',
        'file_url',
        'attachments',
        'ip_address',
        'user_agent',
        'metadata',
        'submitted_at',
    ];

    protected $casts = [
        'answer_data' => 'array',
        'attachments' => 'array',
        'metadata' => 'array',
        'submitted_at' => 'datetime',
    ];

    const int CACHE_TTL = 900; // 15 minutes (shorter for high-volume data)

    protected static function booted(): void
    {
        // Auto-generate UUID when creating
        static::creating(function ($answer) {
            if (empty($answer->uuid)) {
                $answer->uuid = Str::uuid()->toString();
            }
            if (empty($answer->submitted_at)) {
                $answer->submitted_at = now();
            }
        });

        // Clear related caches and update counters when created
        static::created(function ($answer) {
            // Update survey response count
            $answer->survey->incrementResponseCount();

            // Clear caches
            Cache::forget("survey:{$answer->survey_id}:responses");
            Cache::forget("question:{$answer->question_id}:responses");
        });
    }

    /**
     * Get answer by UUID with caching (short TTL for performance)
     */
    public static function findByUuid(string $uuid)
    {
        $cacheKey = "answer:uuid:{$uuid}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($uuid) {
            return self::where('uuid', $uuid)->first();
        });
    }

    /**
     * Relationships
     */
    public function survey()
    {
        return $this->belongsTo(Survey::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the processed answer value based on question type
     */
    public function getProcessedAnswer()
    {
        // For text-based questions
        if (!empty($this->answer_text)) {
            return $this->answer_text;
        }

        // For structured data (multiple choice, ratings, etc.)
        if (!empty($this->answer_data)) {
            return $this->answer_data;
        }

        // For file uploads
        if (!empty($this->file_url) || !empty($this->attachments)) {
            return [
                'file_url' => $this->file_url,
                'attachments' => $this->attachments,
            ];
        }

        return null;
    }

    /**
     * Check if the answer has file attachments
     */
    public function hasAttachments(): bool
    {
        return !empty($this->file_url) || !empty($this->attachments);
    }

    /**
     * Get all attachment URLs
     */
    public function getAllAttachments(): array
    {
        $attachments = [];

        if ($this->file_url) {
            $attachments[] = $this->file_url;
        }

        if ($this->attachments && is_array($this->attachments)) {
            $attachments = array_merge($attachments, $this->attachments);
        }

        return $attachments;
    }

    /**
     * Scopes for efficient querying
     */
    public function scopeForSurvey($query, $surveyId)
    {
        return $query->where('survey_id', $surveyId);
    }

    public function scopeForQuestion($query, $questionId)
    {
        return $query->where('question_id', $questionId);
    }

    public function scopeForRespondent($query, $respondentId, $respondentType = 'anonymous')
    {
        return $query->where('respondent_id', $respondentId)
                    ->where('respondent_type', $respondentType);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('submitted_at', [$startDate, $endDate]);
    }

    public function scopeRecentFirst($query)
    {
        return $query->orderBy('submitted_at', 'desc');
    }

    /**
     * Optimized method to get survey completion rate
     */
    public static function getSurveyCompletionRate($surveyId)
    {
        $cacheKey = "survey:{$surveyId}:completion_rate";

        return Cache::remember($cacheKey, 300, function () use ($surveyId) { // 5 minutes
            $totalQuestions = \DB::table('survey_questions')
                ->where('survey_id', $surveyId)
                ->where('is_active', true)
                ->count();

            if ($totalQuestions === 0) {
                return 0;
            }

            $uniqueRespondents = self::where('survey_id', $surveyId)
                ->distinct('respondent_id')
                ->count();

            $totalAnswers = self::where('survey_id', $surveyId)->count();

            return $uniqueRespondents > 0 ? ($totalAnswers / ($uniqueRespondents * $totalQuestions)) * 100 : 0;
        });
    }

    /**
     * Get response analytics for a survey with caching
     */
    public static function getSurveyAnalytics($surveyId)
    {
        $cacheKey = "survey:{$surveyId}:analytics";

        return Cache::remember($cacheKey, 1800, function () use ($surveyId) { // 30 minutes
            return [
                'total_responses' => self::where('survey_id', $surveyId)->count(),
                'unique_respondents' => self::where('survey_id', $surveyId)
                    ->distinct('respondent_id')->count(),
                'completion_rate' => self::getSurveyCompletionRate($surveyId),
                'first_response' => self::where('survey_id', $surveyId)
                    ->orderBy('submitted_at')->value('submitted_at'),
                'last_response' => self::where('survey_id', $surveyId)
                    ->orderBy('submitted_at', 'desc')->value('submitted_at'),
            ];
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Question extends Model
{
    use HasFactory, Searchable;

    protected $fillable = [
        'uuid',
        'name',
        'question_text',
        'question_type_id',
        'user_id',
        'options',
        'validation_rules',
        'image_url',
        'attachments',
        'is_required',
        'is_active',
        'metadata',
        'usage_count',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'attachments' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'usage_count' => 'integer',
    ];

    const int CACHE_TTL = 1800; // 30 minutes

    protected static function booted(): void
    {
        // Auto-generate UUID when creating
        static::creating(function ($question) {
            if (empty($question->uuid)) {
                $question->uuid = Str::uuid()->toString();
            }
        });

        // Clear related caches when updated
        static::saved(function ($question) {
            Cache::forget("question:uuid:{$question->uuid}");
            Cache::forget("question:id:{$question->id}");
            Cache::forget("user:{$question->user_id}:questions");
            Cache::forget("question_type:{$question->question_type_id}:questions");
        });

        static::deleted(function ($question) {
            Cache::forget("question:uuid:{$question->uuid}");
            Cache::forget("question:id:{$question->id}");
            Cache::forget("user:{$question->user_id}:questions");
        });
    }

    /**
     * Get question by UUID with caching
     */
    public static function findByUuid(string $uuid)
    {
        $cacheKey = "question:uuid:{$uuid}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($uuid) {
            return self::where('uuid', $uuid)->first();
        });
    }

    /**
     * Scout search configuration
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'question_text' => $this->question_text,
            'question_type_id' => $this->question_type_id,
            'is_active' => $this->is_active,
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at->timestamp,
            'user_id' => $this->user_id,
        ];
    }

    /**
     * Get the index name for Scout
     */
    public function searchableAs(): string
    {
        return 'questions_index';
    }

    /**
     * Relationships
     */
    public function questionType()
    {
        return $this->belongsTo(QuestionType::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function surveys()
    {
        return $this->belongsToMany(Survey::class, 'survey_questions')
                    ->withPivot(['order', 'survey_specific_settings', 'is_active'])
                    ->withTimestamps();
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Increment usage count when question is added to a survey
     */
    public function incrementUsage(): void
    {
        $this->increment('usage_count');
        Cache::forget("question:uuid:{$this->uuid}");
        Cache::forget("question:id:{$this->id}");
    }

    /**
     * Decrement usage count when question is removed from survey
     */
    public function decrementUsage(): void
    {
        $this->decrement('usage_count');
        Cache::forget("question:uuid:{$this->uuid}");
        Cache::forget("question:id:{$this->id}");
    }

    /**
     * Get question configuration merged with type defaults
     */
    public function getConfiguration(): array
    {
        $typeConfig = $this->questionType->configuration ?? [];
        $questionConfig = $this->metadata ?? [];

        return array_merge($typeConfig, $questionConfig);
    }

    /**
     * Check if the question allows images
     */
    public function allowsImages()
    {
        return $this->questionType->allows_images ?? false;
    }

    /**
     * Check if the question allows multiple answers
     */
    public function allowsMultipleAnswers()
    {
        return $this->questionType->allows_multiple_answers ?? false;
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByType($query, $typeId)
    {
        return $query->where('question_type_id', $typeId);
    }

    public function scopePopular($query, $limit = 10)
    {
        return $query->orderBy('usage_count', 'desc')->limit($limit);
    }
}

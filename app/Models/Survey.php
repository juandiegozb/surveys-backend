<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Survey extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'description',
        'status',
        'user_id',
        'settings',
        'cover_image_url',
        'starts_at',
        'ends_at',
        'is_public',
        'question_count',
        'response_count',
    ];

    protected $casts = [
        'settings' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_public' => 'boolean',
        'question_count' => 'integer',
        'response_count' => 'integer',
    ];

    protected $hidden = ['user_id'];

    const int CACHE_TTL = 1800; // 30 minutes
    const array STATUSES = ['draft', 'active', 'paused', 'completed', 'archived'];

    protected static function booted(): void
    {
        // Auto-generate UUID when creating
        static::creating(function ($survey) {
            if (empty($survey->uuid)) {
                $survey->uuid = Str::uuid()->toString();
            }
        });

        // Clear related caches when updated
        static::saved(function ($survey) {
            $survey->clearCache();
        });

        static::deleted(function ($survey) {
            $survey->clearCache();
        });
    }

    /**
     * Get survey by UUID with caching
     */
    public static function findByUuid(string $uuid)
    {
        $cacheKey = "survey:uuid:{$uuid}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($uuid) {
            return self::where('uuid', $uuid)->first();
        });
    }

    /**
     * Clear all caches for this survey
     */
    public function clearCache(): void
    {
        Cache::forget("survey:uuid:{$this->uuid}");
        Cache::forget("survey:id:{$this->id}");
        Cache::forget("user:{$this->user_id}:surveys");
    }

    /**
     * Get survey by UUID without caching (for fresh data)
     */
    public static function findByUuidFresh(string $uuid)
    {
        return self::where('uuid', $uuid)->first();
    }

    /**
     * Relationships
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->belongsToMany(Question::class, 'survey_questions')
                    ->withPivot(['order', 'survey_specific_settings', 'is_active'])
                    ->withTimestamps()
                    ->orderBy('survey_questions.order');
    }

    public function answers()
    {
        return $this->hasMany(Answer::class);
    }

    /**
     * Get active questions for this survey with caching
     */
    public function getActiveQuestions()
    {
        $cacheKey = "survey:{$this->id}:questions:active";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () {
            return $this->questions()
                        ->wherePivot('is_active', true)
                        ->where('questions.is_active', true)
                        ->get();
        });
    }

    /**
     * Increment response count efficiently
     */
    public function incrementResponseCount(): void
    {
        $this->increment('response_count');
        Cache::forget("survey:uuid:{$this->uuid}");
        Cache::forget("survey:id:{$this->id}");
    }

    /**
     * Scope for active surveys
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for public surveys
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for user's surveys
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}

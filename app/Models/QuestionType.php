<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class QuestionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'configuration',
        'allows_images',
        'allows_multiple_answers',
        'is_active',
    ];

    protected $casts = [
        'configuration' => 'array',
        'allows_images' => 'boolean',
        'allows_multiple_answers' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Cache key for all active question types
    const string CACHE_KEY_ACTIVE = 'question_types:active';
    const int CACHE_TTL = 3600; // 1 hour

    /**
     * Get all active question types with Redis caching
     */
    public static function getActive()
    {
        return Cache::remember(self::CACHE_KEY_ACTIVE, self::CACHE_TTL, function () {
            return self::where('is_active', true)->orderBy('display_name')->get();
        });
    }

    /**
     * Get question type by name with caching
     */
    public static function getByName(string $name)
    {
        $cacheKey = "question_type:name:{$name}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($name) {
            return self::where('name', $name)->where('is_active', true)->first();
        });
    }

    /**
     * Relationship with questions
     */
    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    /**
     * Clear cache when the model is updated
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget(self::CACHE_KEY_ACTIVE);
        });

        static::deleted(function () {
            Cache::forget(self::CACHE_KEY_ACTIVE);
        });
    }
}

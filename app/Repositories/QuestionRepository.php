<?php

namespace App\Repositories;

use App\Models\Question;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class QuestionRepository
{
    /**
     * Get a paginated list of questions with optional filtering
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Question::query();

        // Apply filters
        if (isset($filters['question_type_id']) && $filters['question_type_id']) {
            $query->where('question_type_id', $filters['question_type_id']);
        }

        if (isset($filters['search']) && $filters['search']) {
            // For large datasets, we'll use Laravel Scout/OpenSearch if available
            // Fall back to database search in testing environment
            if (config('scout.driver') !== null && app()->environment() !== 'testing') {
                try {
                    return Question::search($filters['search'])
                                ->query(function ($query) use ($filters) {
                                    $this->applyAdditionalFilters($query, $filters);
                                })
                                ->paginate($perPage);
                } catch (\Exception $e) {
                    // Fall back to database search if Scout fails
                }
            }

            // Database fallback search
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('question_text', 'like', '%' . $filters['search'] . '%');
            });
        }

        $this->applyAdditionalFilters($query, $filters);

        // Order by most recent by default
        $query->latest();

        // Always load the question type relationship
        $query->with('questionType');

        return $query->paginate($perPage);
    }

    /**
     * Apply additional filters to the query
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return void
     */
    private function applyAdditionalFilters($query, array $filters): void
    {
        if (isset($filters['user_id']) && $filters['user_id']) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== null) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        if (isset($filters['survey_uuid']) && $filters['survey_uuid']) {
            $query->whereHas('surveys', function ($q) use ($filters) {
                $q->where('uuid', $filters['survey_uuid']);
            });
        }
    }

    /**
     * Find a question by UUID
     *
     * @param string $uuid
     * @return Question|null
     */
    public function findByUuid(string $uuid): ?Question
    {
        // Use the cached method from the model
        return Question::findByUuid($uuid);
    }

    /**
     * Find multiple questions by their UUIDs
     *
     * @param array $uuids
     * @return Collection
     */
    public function findManyByUuids(array $uuids): Collection
    {
        return Question::whereIn('uuid', $uuids)->get();
    }

    /**
     * Create a new question
     *
     * @param array $data
     * @return Question
     */
    public function create(array $data): Question
    {
        return Question::create($data);
    }

    /**
     * Update an existing question
     *
     * @param Question $question
     * @param array $data
     * @return Question
     */
    public function update(Question $question, array $data): Question
    {
        $question->update($data);
        return $question;
    }

    /**
     * Delete a question
     *
     * @param Question $question
     * @return bool
     */
    public function delete(Question $question): bool
    {
        return (bool) $question->delete();
    }

    /**
     * Increment usage count for a question
     *
     * @param Question $question
     * @return void
     */
    public function incrementUsageCount(Question $question): void
    {
        $question->incrementUsage();
    }
}

<?php

namespace App\Repositories;

use App\Models\Survey;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class SurveyRepository
{
    /**
     * Get a paginated list of surveys with optional filtering
     *
     * @param int $perPage
     * @param array $filters
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage, array $filters = []): LengthAwarePaginator
    {
        $query = Survey::query();

        // Apply filters
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['search']) && $filters['search']) {
            // For large datasets, we'll use Laravel Scout/OpenSearch if available
            // Fall back to database search in testing environment
            if (config('scout.driver') !== null && app()->environment() !== 'testing') {
                try {
                    return Survey::search($filters['search'])
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
                  ->orWhere('description', 'like', '%' . $filters['search'] . '%');
            });
        }

        $this->applyAdditionalFilters($query, $filters);

        // Order by most recent by default
        $query->latest();

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

        if (isset($filters['is_public']) && $filters['is_public'] !== null) {
            $query->where('is_public', (bool) $filters['is_public']);
        }

        if (isset($filters['from_date']) && $filters['from_date']) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if (isset($filters['to_date']) && $filters['to_date']) {
            $query->where('created_at', '<=', $filters['to_date']);
        }
    }

    /**
     * Find a survey by UUID
     *
     * @param string $uuid
     * @return Survey|null
     */
    public function findByUuid(string $uuid): ?Survey
    {
        // Use the cached method from the model
        return Survey::findByUuid($uuid);
    }

    /**
     * Find a survey by UUID with fresh data (no cache)
     *
     * @param string $uuid
     * @return Survey|null
     */
    public function findByUuidFresh(string $uuid): ?Survey
    {
        return Survey::findByUuidFresh($uuid);
    }

    /**
     * Create a new survey
     *
     * @param array $data
     * @return Survey
     */
    public function create(array $data): Survey
    {
        return Survey::create($data);
    }

    /**
     * Update an existing survey
     *
     * @param Survey $survey
     * @param array $data
     * @return Survey
     */
    public function update(Survey $survey, array $data): Survey
    {
        $survey->update($data);
        return $survey;
    }

    /**
     * Delete a survey
     *
     * @param Survey $survey
     * @return bool
     */
    public function delete(Survey $survey): bool
    {
        return (bool) $survey->delete();
    }

    /**
     * Update question count for a survey
     *
     * @param Survey $survey
     * @param int $count
     * @return void
     */
    public function updateQuestionCount(Survey $survey, int $count): void
    {
        $survey->question_count = $count;
        $survey->save();
    }
}

<?php

namespace App\Repositories;

use App\Models\Survey;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class SurveyRepository
{
    protected Survey $model;
    const int CACHE_TTL = 1800; // 30 minutes

    /**
     * Create a new class instance.
     */
    public function __construct(Survey $model)
    {
        $this->model = $model;
    }

    /**
     * Get all surveys with efficient pagination and caching
     */
    public function getAll($perPage = 15, $filters = [])
    {
        $cacheKey = 'surveys:all:' . md5(serialize($filters)) . ':page:' . request('page', 1);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage, $filters) {
            $query = $this->model->with(['user:id,name', 'questionType:id,display_name']);

            // Apply filters
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['is_public'])) {
                $query->where('is_public', $filters['is_public']);
            }

            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'LIKE', "%{$filters['search']}%")
                      ->orWhere('description', 'LIKE', "%{$filters['search']}%");
                });
            }

            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });
    }

    /**
     * Find survey by ID with caching
     */
    public function find($id)
    {
        $cacheKey = "survey:id:{$id}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return $this->model->with(['user:id,name,email', 'questions.questionType'])->find($id);
        });
    }

    /**
     * Find survey by UUID with caching
     */
    public function findByUuid($uuid)
    {
        return $this->model->findByUuid($uuid);
    }

    /**
     * Create a new survey
     */
    public function create(array $data)
    {
        DB::beginTransaction();
        try {
            $survey = $this->model->create($data);

            // Clear related caches
            Cache::forget("user:{$survey->user_id}:surveys");
            Cache::tags(['surveys'])->flush();

            DB::commit();
            return $survey;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update survey
     */
    public function update($id, array $data)
    {
        DB::beginTransaction();
        try {
            $survey = $this->find($id);
            if (!$survey) {
                return null;
            }

            $survey->update($data);

            // Clear related caches
            Cache::forget("survey:id:{$id}");
            Cache::forget("survey:uuid:{$survey->uuid}");
            Cache::forget("user:{$survey->user_id}:surveys");
            Cache::tags(['surveys'])->flush();

            DB::commit();
            return $survey->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Delete survey
     */
    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $survey = $this->find($id);
            if (!$survey) {
                return false;
            }

            // Store data for cache clearing
            $userId = $survey->user_id;
            $uuid = $survey->uuid;

            $survey->delete();

            // Clear related caches
            Cache::forget("survey:id:{$id}");
            Cache::forget("survey:uuid:{$uuid}");
            Cache::forget("user:{$userId}:surveys");
            Cache::tags(['surveys'])->flush();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get surveys for specific user with caching
     */
    public function getUserSurveys($userId, $perPage = 15)
    {
        $cacheKey = "user:{$userId}:surveys:page:" . request('page', 1);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $perPage) {
            return $this->model->forUser($userId)
                              ->with(['questions' => function ($query) {
                                  $query->wherePivot('is_active', true);
                              }])
                              ->orderBy('updated_at', 'desc')
                              ->paginate($perPage);
        });
    }

    /**
     * Get public active surveys with caching
     */
    public function getPublicSurveys($perPage = 15)
    {
        $cacheKey = "surveys:public:page:" . request('page', 1);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage) {
            return $this->model->public()
                              ->active()
                              ->with(['user:id,name'])
                              ->orderBy('created_at', 'desc')
                              ->paginate($perPage);
        });
    }

    /**
     * Search surveys using Scout
     */
    public function search($query, $perPage = 15)
    {
        return $this->model->search($query)->paginate($perPage);
    }

    /**
     * Get survey statistics with heavy caching
     */
    public function getStatistics($surveyId)
    {
        $cacheKey = "survey:{$surveyId}:statistics";

        return Cache::remember($cacheKey, 900, function () use ($surveyId) { // 15 minutes
            $survey = $this->find($surveyId);
            if (!$survey) {
                return null;
            }

            return [
                'total_questions' => $survey->question_count,
                'total_responses' => $survey->response_count,
                'completion_rate' => \App\Models\Answer::getSurveyCompletionRate($surveyId),
                'average_time' => $this->getAverageCompletionTime($surveyId),
                'response_trend' => $this->getResponseTrend($surveyId),
            ];
        });
    }

    /**
     * Assign questions to survey
     */
    public function assignQuestions($surveyId, array $questionIds)
    {
        DB::beginTransaction();
        try {
            $survey = $this->find($surveyId);
            if (!$survey) {
                return false;
            }

            // Prepare pivot data with order
            $pivotData = [];
            foreach ($questionIds as $index => $questionId) {
                $pivotData[$questionId] = [
                    'order' => $index + 1,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            $survey->questions()->sync($pivotData);

            // Update question count
            $survey->update(['question_count' => count($questionIds)]);

            // Update usage count for questions
            foreach ($questionIds as $questionId) {
                \App\Models\Question::find($questionId)?->incrementUsage();
            }

            // Clear caches
            Cache::forget("survey:{$surveyId}:questions:active");
            Cache::forget("survey:id:{$surveyId}");

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get average completion time for survey
     */
    private function getAverageCompletionTime($surveyId)
    {
        return Cache::remember("survey:{$surveyId}:avg_time", 3600, function () use ($surveyId) {
            // This would require additional tracking in answers table
            // For now, return null - can be implemented with more detailed tracking
            return null;
        });
    }

    /**
     * Get response trend data
     */
    private function getResponseTrend($surveyId, $days = 30)
    {
        return Cache::remember("survey:{$surveyId}:trend:{$days}d", 1800, function () use ($surveyId, $days) {
            return DB::table('answers')
                     ->select(DB::raw('DATE(submitted_at) as date'), DB::raw('COUNT(*) as count'))
                     ->where('survey_id', $surveyId)
                     ->where('submitted_at', '>=', now()->subDays($days))
                     ->groupBy('date')
                     ->orderBy('date')
                     ->get();
        });
    }

    /**
     * Bulk operations for mass updates
     */
    public function bulkUpdateStatus(array $surveyIds, $status)
    {
        DB::beginTransaction();
        try {
            $this->model->whereIn('id', $surveyIds)->update(['status' => $status]);

            // Clear caches for affected surveys
            foreach ($surveyIds as $id) {
                Cache::forget("survey:id:{$id}");
            }
            Cache::tags(['surveys'])->flush();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }
}

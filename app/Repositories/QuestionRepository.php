<?php

namespace App\Repositories;

use App\Models\Question;
use App\Models\QuestionType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionRepository
{
    protected $model;
    const int CACHE_TTL = 1800; // 30 minutes

    /**
     * Create a new class instance.
     */
    public function __construct(Question $model)
    {
        $this->model = $model;
    }

    /**
     * Get all questions with filtering and caching
     */
    public function getAll($perPage = 15, $filters = [])
    {
        $cacheKey = 'questions:all:' . md5(serialize($filters)) . ':page:' . request('page', 1);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($perPage, $filters) {
            $query = $this->model->with(['questionType:id,name,display_name', 'user:id,name']);

            // Apply filters
            if (isset($filters['question_type_id'])) {
                $query->where('question_type_id', $filters['question_type_id']);
            }

            if (isset($filters['user_id'])) {
                $query->where('user_id', $filters['user_id']);
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'LIKE', "%{$filters['search']}%")
                      ->orWhere('question_text', 'LIKE', "%{$filters['search']}%");
                });
            }

            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });
    }

    /**
     * Find question by ID with caching
     */
    public function find($id)
    {
        $cacheKey = "question:id:{$id}";
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return $this->model->with(['questionType', 'user:id,name'])->find($id);
        });
    }

    /**
     * Find question by UUID
     */
    public function findByUuid($uuid)
    {
        return $this->model->findByUuid($uuid);
    }

    /**
     * Create new question with file uploads
     */
    public function create(array $data, $imageFile = null, $attachments = [])
    {
        DB::beginTransaction();
        try {
            // Handle image upload to S3
            if ($imageFile) {
                $imagePath = Storage::disk('s3')->put('questions/images', $imageFile);
                $data['image_url'] = Storage::disk('s3')->url($imagePath);
            }

            // Handle multiple attachments
            if (!empty($attachments)) {
                $attachmentUrls = [];
                foreach ($attachments as $file) {
                    $filePath = Storage::disk('s3')->put('questions/attachments', $file);
                    $attachmentUrls[] = Storage::disk('s3')->url($filePath);
                }
                $data['attachments'] = $attachmentUrls;
            }

            $question = $this->model->create($data);

            // Clear related caches
            Cache::forget("user:{$question->user_id}:questions");
            Cache::forget("question_type:{$question->question_type_id}:questions");
            Cache::tags(['questions'])->flush();

            DB::commit();
            return $question;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Update question
     */
    public function update($id, array $data, $imageFile = null, $attachments = [])
    {
        DB::beginTransaction();
        try {
            $question = $this->find($id);
            if (!$question) {
                return null;
            }

            // Handle new image upload
            if ($imageFile) {
                // Delete old image if exists
                if ($question->image_url) {
                    $this->deleteFileFromUrl($question->image_url);
                }

                $imagePath = Storage::disk('s3')->put('questions/images', $imageFile);
                $data['image_url'] = Storage::disk('s3')->url($imagePath);
            }

            // Handle new attachments
            if (!empty($attachments)) {
                // Delete old attachments if exists
                if ($question->attachments) {
                    foreach ($question->attachments as $url) {
                        $this->deleteFileFromUrl($url);
                    }
                }

                $attachmentUrls = [];
                foreach ($attachments as $file) {
                    $filePath = Storage::disk('s3')->put('questions/attachments', $file);
                    $attachmentUrls[] = Storage::disk('s3')->url($filePath);
                }
                $data['attachments'] = $attachmentUrls;
            }

            $question->update($data);

            // Clear related caches
            Cache::forget("question:id:{$id}");
            Cache::forget("question:uuid:{$question->uuid}");
            Cache::forget("user:{$question->user_id}:questions");
            Cache::tags(['questions'])->flush();

            DB::commit();
            return $question->fresh();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Delete question and associated files
     */
    public function delete($id)
    {
        DB::beginTransaction();
        try {
            $question = $this->find($id);
            if (!$question) {
                return false;
            }

            // Store data for cache clearing
            $userId = $question->user_id;
            $uuid = $question->uuid;

            // Delete associated files from S3
            if ($question->image_url) {
                $this->deleteFileFromUrl($question->image_url);
            }

            if ($question->attachments) {
                foreach ($question->attachments as $url) {
                    $this->deleteFileFromUrl($url);
                }
            }

            $question->delete();

            // Clear related caches
            Cache::forget("question:id:{$id}");
            Cache::forget("question:uuid:{$uuid}");
            Cache::forget("user:{$userId}:questions");
            Cache::tags(['questions'])->flush();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get questions by type with caching
     */
    public function getByType($typeId, $perPage = 15)
    {
        $cacheKey = "question_type:{$typeId}:questions:page:" . request('page', 1);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($typeId, $perPage) {
            return $this->model->byType($typeId)
                              ->active()
                              ->with(['user:id,name'])
                              ->orderBy('usage_count', 'desc')
                              ->paginate($perPage);
        });
    }

    /**
     * Get popular questions with caching
     */
    public function getPopular($limit = 10)
    {
        $cacheKey = "questions:popular:{$limit}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($limit) {
            return $this->model->popular($limit)
                              ->active()
                              ->with(['questionType:id,display_name', 'user:id,name'])
                              ->get();
        });
    }

    /**
     * Search questions using Scout
     */
    public function search($query, $perPage = 15)
    {
        return $this->model->search($query)
                          ->where('is_active', true)
                          ->paginate($perPage);
    }

    /**
     * Mass operations for questions
     */
    public function bulkAssignToSurveys(array $questionIds, array $surveyIds)
    {
        DB::beginTransaction();
        try {
            foreach ($surveyIds as $surveyId) {
                foreach ($questionIds as $index => $questionId) {
                    DB::table('survey_questions')->updateOrInsert(
                        ['survey_id' => $surveyId, 'question_id' => $questionId],
                        [
                            'order' => $index + 1,
                            'is_active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                // Update survey question count
                $questionCount = count($questionIds);
                DB::table('surveys')
                  ->where('id', $surveyId)
                  ->update(['question_count' => $questionCount]);
            }

            // Update usage counts
            $this->model->whereIn('id', $questionIds)->increment('usage_count');

            // Clear related caches
            foreach ($questionIds as $questionId) {
                Cache::forget("question:id:{$questionId}");
            }
            foreach ($surveyIds as $surveyId) {
                Cache::forget("survey:id:{$surveyId}");
                Cache::forget("survey:{$surveyId}:questions:active");
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Bulk delete questions
     */
    public function bulkDelete(array $questionIds)
    {
        DB::beginTransaction();
        try {
            $questions = $this->model->whereIn('id', $questionIds)->get();

            foreach ($questions as $question) {
                // Delete associated files
                if ($question->image_url) {
                    $this->deleteFileFromUrl($question->image_url);
                }

                if ($question->attachments) {
                    foreach ($question->attachments as $url) {
                        $this->deleteFileFromUrl($url);
                    }
                }
            }

            $this->model->whereIn('id', $questionIds)->delete();

            // Clear caches
            foreach ($questionIds as $id) {
                Cache::forget("question:id:{$id}");
            }
            Cache::tags(['questions'])->flush();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    /**
     * Get user's questions with caching
     */
    public function getUserQuestions($userId, $perPage = 15)
    {
        $cacheKey = "user:{$userId}:questions:page:" . request('page', 1);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($userId, $perPage) {
            return $this->model->forUser($userId)
                              ->with(['questionType:id,display_name'])
                              ->orderBy('updated_at', 'desc')
                              ->paginate($perPage);
        });
    }

    /**
     * Delete file from S3 using URL
     */
    private function deleteFileFromUrl($url)
    {
        try {
            $path = parse_url($url, PHP_URL_PATH);
            $path = ltrim($path, '/');

            // Remove bucket name from path if present
            $bucketName = config('filesystems.disks.s3.bucket');
            if (strpos($path, $bucketName . '/') === 0) {
                $path = substr($path, strlen($bucketName) + 1);
            }

            Storage::disk('s3')->delete($path);
        } catch (\Exception $e) {
            // Log error but don't fail the operation
            \Log::warning("Failed to delete file from S3: {$url}", ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get question statistics
     */
    public function getStatistics($questionId)
    {
        $cacheKey = "question:{$questionId}:statistics";

        return Cache::remember($cacheKey, 900, function () use ($questionId) {
            $question = $this->find($questionId);
            if (!$question) {
                return null;
            }

            return [
                'usage_count' => $question->usage_count,
                'total_answers' => $question->answers()->count(),
                'surveys_using' => $question->surveys()->count(),
                'answer_rate' => $this->getAnswerRate($questionId),
            ];
        });
    }

    /**
     * Get answer rate for question
     */
    private function getAnswerRate($questionId)
    {
        $totalShown = DB::table('survey_questions')
                       ->join('answers', 'survey_questions.survey_id', '=', 'answers.survey_id')
                       ->where('survey_questions.question_id', $questionId)
                       ->distinct('answers.respondent_id')
                       ->count();

        $totalAnswered = DB::table('answers')
                          ->where('question_id', $questionId)
                          ->distinct('respondent_id')
                          ->count();

        return $totalShown > 0 ? ($totalAnswered / $totalShown) * 100 : 0;
    }
}

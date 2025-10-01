<?php

namespace App\Jobs;

use App\Models\Survey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexSurveyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $survey;

    /**
     * Create a new job instance.
     */
    public function __construct(Survey $survey)
    {
        $this->survey = $survey;
        $this->onQueue('search-indexing'); // Dedicated queue for search operations
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Index the survey for full-text search
            $this->survey->searchable();

            Log::info("Survey indexed successfully", [
                'survey_id' => $this->survey->id,
                'survey_name' => $this->survey->name
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to index survey", [
                'survey_id' => $this->survey->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Survey indexing job failed", [
            'survey_id' => $this->survey->id,
            'exception' => $exception->getMessage()
        ]);
    }
}

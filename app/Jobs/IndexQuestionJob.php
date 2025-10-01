<?php

namespace App\Jobs;

use App\Models\Question;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class IndexQuestionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $question;

    /**
     * Create a new job instance.
     */
    public function __construct(Question $question)
    {
        $this->question = $question;
        $this->onQueue('search-indexing'); // Dedicated queue for search operations
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Index the question for full-text search
            $this->question->searchable();

            Log::info("Question indexed successfully", [
                'question_id' => $this->question->id,
                'question_name' => $this->question->name
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to index question", [
                'question_id' => $this->question->id,
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
        Log::error("Question indexing job failed", [
            'question_id' => $this->question->id,
            'exception' => $exception->getMessage()
        ]);
    }
}

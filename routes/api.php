<?php

use App\Http\Controllers\Api\QuestionController;
use App\Http\Controllers\Api\QuestionTypeController;
use App\Http\Controllers\Api\SurveyController;
use App\Http\Controllers\Api\AnswerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider, and all of them will
| be assigned to the "api" middleware group and rate limiting.
|
*/

// Apply rate limiting middleware to all API routes
Route::middleware(['api.rate.limit'])->group(function () {

    // Survey routes
    Route::apiResource('surveys', SurveyController::class);

    // Question routes
    Route::apiResource('questions', QuestionController::class);

    // Answer routes
    Route::apiResource('answers', AnswerController::class);

    // Question Type routes
    Route::get('question-types', [QuestionTypeController::class, 'index'])->name('question-types.index');
    Route::get('question-types/{questionType}', [QuestionTypeController::class, 'show'])->name('question-types.show');

    // Bulk operations
    Route::prefix('bulk')->name('bulk.')->group(function () {
        Route::post('surveys', function() {
            return response()->json(['message' => 'Bulk surveys endpoint - coming soon'], 501);
        })->name('surveys');

        Route::post('questions', function() {
            return response()->json(['message' => 'Bulk questions endpoint - coming soon'], 501);
        })->name('questions');

        Route::post('answers', function() {
            return response()->json(['message' => 'Bulk answers endpoint - coming soon'], 501);
        })->name('answers');
    });

});

// Health check endpoint (without rate limiting)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toISOString(),
        'service' => 'surveys-api',
        'version' => '1.0.0'
    ]);
});

// API Documentation endpoint (without rate limiting)
Route::get('/docs', function () {
    return response()->json([
        'api_name' => 'Surveys API',
        'version' => '1.0.0',
        'rate_limits' => [
            'read_operations' => '200 per minute',
            'write_operations' => '60 per minute',
            'bulk_operations' => '10 per minute'
        ],
        'endpoints' => [
            'surveys' => [
                'GET /api/surveys' => 'List surveys',
                'POST /api/surveys' => 'Create survey',
                'GET /api/surveys/{id}' => 'Show survey',
                'PUT /api/surveys/{id}' => 'Update survey',
                'DELETE /api/surveys/{id}' => 'Delete survey'
            ],
            'questions' => [
                'GET /api/questions' => 'List questions',
                'POST /api/questions' => 'Create question',
                'GET /api/questions/{id}' => 'Show question',
                'PUT /api/questions/{id}' => 'Update question',
                'DELETE /api/questions/{id}' => 'Delete question'
            ],
            'answers' => [
                'GET /api/answers' => 'List answers',
                'POST /api/answers' => 'Create answer',
                'GET /api/answers/{id}' => 'Show answer',
                'PUT /api/answers/{id}' => 'Update answer',
                'DELETE /api/answers/{id}' => 'Delete answer'
            ],
            'question_types' => [
                'GET /api/question-types' => 'List question types',
                'GET /api/question-types/{id}' => 'Show question type'
            ]
        ]
    ]);
});

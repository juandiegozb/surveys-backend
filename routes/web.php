<?php

use App\Livewire\SurveyList;
use App\Livewire\SurveyDetails;
use App\Livewire\QuestionList;
use App\Livewire\Dashboard;
use App\Livewire\PublicSurveyForm;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider, and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home/Welcome route
Route::get('/', function () {
    return redirect()->route('web.dashboard');
});

// Frontend Web Routes (Livewire) - Use 'web.' prefix to distinguish from API routes
Route::get('/dashboard', Dashboard::class)->name('web.dashboard');
Route::get('/surveys', SurveyList::class)->name('web.surveys.index');
Route::get('/surveys/{uuid}', SurveyDetails::class)->name('web.surveys.show');
Route::get('/questions', QuestionList::class)->name('web.questions.index');

// Public Survey Routes
Route::get('/survey/{uuid}', PublicSurveyForm::class)->name('survey.public');
Route::get('/s/{uuid}', PublicSurveyForm::class)->name('survey.short');

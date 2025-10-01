<?php

namespace App\Livewire;

use App\Models\Survey;
use App\Models\Question;
use App\Models\Answer;
use Livewire\Component;

class Dashboard extends Component
{
    public $totalSurveys = 0;
    public $totalQuestions = 0;
    public $totalResponses = 0;
    public $recentSurveys = [];

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function render()
    {
        return view('livewire.dashboard')->layout('layouts.app');
    }

    public function loadDashboardData()
    {
        try {
            $this->totalSurveys = Survey::count();
            $this->totalQuestions = Question::count();
            $this->totalResponses = Answer::distinct('respondent_id')->count();

            $this->recentSurveys = Survey::with(['questions'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error loading dashboard data: ' . $e->getMessage());
        }
    }
}

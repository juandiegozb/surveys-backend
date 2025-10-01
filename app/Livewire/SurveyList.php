<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\SurveyService;
use Illuminate\Support\Facades\Log;

class SurveyList extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;

    // Form fields
    public $name = '';
    public $description = '';
    public $surveyStatus = 'draft';
    public $isPublic = false;
    public $selectedSurvey = null;

    // Data
    public $surveys = [];
    public $totalSurveys = 0;
    public $currentPage = 1;
    public $lastPage = 1;
    public $loading = false;

    protected SurveyService $surveyService;

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:2000',
        'surveyStatus' => 'required|in:draft,active,paused,completed,archived',
        'isPublic' => 'boolean',
    ];

    public function boot(SurveyService $surveyService): void
    {
        $this->surveyService = $surveyService;
    }

    public function mount(): void
    {
        $this->loadSurveys();
    }

    public function updatedSearch(): void
    {
        $this->currentPage = 1;
        $this->loadSurveys();
    }

    public function updatedStatus(): void
    {
        $this->currentPage = 1;
        $this->loadSurveys();
    }

    public function render()
    {
        return view('livewire.survey-list')->layout('layouts.app');
    }

    public function loadSurveys(): void
    {
        $this->loading = true;

        try {
            $filters = [];

            if ($this->search) {
                $filters['search'] = $this->search;
            }

            if ($this->status) {
                $filters['status'] = $this->status;
            }

            // Add pagination to filters
            $filters['page'] = $this->currentPage;

            // Use SurveyService directly instead of HTTP calls
            $paginatedSurveys = $this->surveyService->list(10, $filters);

            // Convert to array for Livewire
            $this->surveys = $paginatedSurveys->items();
            $this->totalSurveys = $paginatedSurveys->total();
            $this->currentPage = $paginatedSurveys->currentPage();
            $this->lastPage = $paginatedSurveys->lastPage();

            Log::info('Surveys loaded successfully', [
                'total' => $this->totalSurveys,
                'count' => count($this->surveys),
                'current_page' => $this->currentPage,
                'last_page' => $this->lastPage,
                'search' => $this->search,
                'status' => $this->status
            ]);

        } catch (\Exception $e) {
            $this->surveys = [];
            $this->totalSurveys = 0;
            $this->currentPage = 1;
            $this->lastPage = 1;

            Log::error('Error loading surveys', [
                'error' => $e->getMessage(),
                'search' => $this->search,
                'status' => $this->status
            ]);

            $this->dispatch('toast', message: 'Error loading surveys: ' . $e->getMessage(), type: 'error');
        }

        $this->loading = false;
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->resetForm();
    }

    public function createSurvey(): void
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->surveyStatus,
                'is_public' => $this->isPublic,
                'user_id' => auth()->id() ?? 1
            ];

            $this->surveyService->create($data);

            $this->dispatch('toast', message: 'Survey created successfully!', type: 'success');
            $this->closeCreateModal();
            $this->loadSurveys();

        } catch (\Exception $e) {
            Log::error('Error creating survey', [
                'error' => $e->getMessage(),
                'data' => $data ?? []
            ]);

            $this->dispatch('toast', message: 'Error creating survey: ' . $e->getMessage(), type: 'error');
        }
    }

    public function editSurvey($surveyUuid): void
    {
        // This method is called from the frontend, delegate to openEditModal
        $this->openEditModal($surveyUuid);
    }

    public function openEditModal($surveyUuid): void
    {
        try {
            $this->selectedSurvey = $this->surveyService->findByUuid($surveyUuid);
            $this->name = $this->selectedSurvey->name;
            $this->description = $this->selectedSurvey->description;
            $this->surveyStatus = $this->selectedSurvey->status;
            $this->isPublic = $this->selectedSurvey->is_public;
            $this->showEditModal = true;

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Survey not found', type: 'error');
        }
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->resetForm();
    }

    public function updateSurvey(): void
    {
        $this->validate();

        try {
            $data = [
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->surveyStatus,
                'is_public' => $this->isPublic,
            ];

            $this->surveyService->update($this->selectedSurvey->uuid, $data);

            $this->dispatch('toast', message: 'Survey updated successfully!', type: 'success');
            $this->closeEditModal();
            $this->loadSurveys();

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Error updating survey: ' . $e->getMessage(), type: 'error');
        }
    }

    public function confirmDelete($surveyUuid): void
    {
        try {
            $this->selectedSurvey = $this->surveyService->findByUuid($surveyUuid);
            $this->showDeleteModal = true;

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Survey not found', type: 'error');
        }
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->selectedSurvey = null;
    }

    public function deleteSurvey(): void
    {
        try {
            $this->surveyService->delete($this->selectedSurvey->uuid);

            $this->dispatch('toast', message: 'Survey deleted successfully!', type: 'success');
            $this->closeDeleteModal();
            $this->loadSurveys();

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Error deleting survey: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeModals(): void
    {
        // Close all modals and reset the form
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->resetForm();
    }

    public function clearFilters(): void
    {
        // Reset all filter properties
        $this->search = '';
        $this->status = '';
        $this->currentPage = 1;

        // Reload surveys with cleared filters
        $this->loadSurveys();

        // Show a success message
        $this->dispatch('toast', message: 'Filters cleared successfully!', type: 'success');
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadSurveys();
        }
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->lastPage) {
            $this->currentPage++;
            $this->loadSurveys();
        }
    }

    public function gotoPage($page): void
    {
        if ($page >= 1 && $page <= $this->lastPage) {
            $this->currentPage = $page;
            $this->loadSurveys();
        }
    }

    private function resetForm(): void
    {
        $this->name = '';
        $this->description = '';
        $this->surveyStatus = 'draft';
        $this->isPublic = false;
        $this->selectedSurvey = null;
        $this->resetErrorBag();
    }
}

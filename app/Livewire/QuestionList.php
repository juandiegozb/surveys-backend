<?php

namespace App\Livewire;

use App\Models\Question;
use App\Models\QuestionType;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class QuestionList extends Component
{
    use WithPagination;

    public $search = '';
    public $questionTypeFilter = '';
    public $showCreateModal = false;
    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showBulkDeleteModal = false;

    // Form fields
    public $name = '';
    public $questionText = '';
    public $questionTypeId = '';
    public $options = [''];
    public $isRequired = false;
    public $isActive = true;
    public $selectedQuestion = null;

    // Bulk operations
    public $selectedQuestions = [];
    public $selectAll = false;

    protected $rules = [
        'name' => 'required|string|max:255',
        'questionText' => 'required|string|max:2000',
        'questionTypeId' => 'required|exists:question_types,id',
        'options' => 'nullable|array',
        'options.*' => 'nullable|string|max:255',
        'isRequired' => 'boolean',
        'isActive' => 'boolean',
    ];

    public function mount(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedQuestionTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selectedQuestions = $this->getQuestions()->pluck('id')->toArray();
        } else {
            $this->selectedQuestions = [];
        }

        // Force re-render to sync checkbox states
        $this->dispatch('$refresh');
    }

    public function clearSelection(): void
    {
        $this->selectedQuestions = [];
        $this->selectAll = false;

        // Force component refresh to update checkbox states
        $this->dispatch('$refresh');
    }

    public function render()
    {
        $questions = $this->getQuestions();
        $questionTypes = QuestionType::all();

        // Update selectAll state based on current selection
        $totalQuestions = $questions->count();
        if ($totalQuestions > 0) {
            $this->selectAll = count($this->selectedQuestions) === $totalQuestions;
        } else {
            $this->selectAll = false;
        }

        return view('livewire.question-list', [
            'questions' => $questions,
            'questionTypes' => $questionTypes
        ])->layout('layouts.app');
    }

    private function getQuestions()
    {
        $query = Question::with('questionType');

        if ($this->search) {
            $query->where(function($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('question_text', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->questionTypeFilter) {
            $query->where('question_type_id', $this->questionTypeFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate(10);
    }

    public function createQuestion(): void
    {
        $this->validate();

        try {
            $user = User::first() ?? User::factory()->create();

            // Filter out empty options
            $filteredOptions = array_filter($this->options, function($option) {
                return !empty(trim($option));
            });

            Question::create([
                'name' => $this->name,
                'question_text' => $this->questionText,
                'question_type_id' => $this->questionTypeId,
                'options' => !empty($filteredOptions) ? array_values($filteredOptions) : null,
                'is_required' => $this->isRequired,
                'is_active' => $this->isActive,
                'user_id' => $user->id,
                'uuid' => \Str::uuid(),
            ]);

            $this->dispatch('toast', message: 'Question created successfully!', type: 'success');
            $this->resetForm();
            $this->showCreateModal = false;

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Error creating question: ' . $e->getMessage(), type: 'error');
        }
    }

    public function editQuestion($questionId)
    {
        $question = Question::find($questionId);
        if (!$question) {
            $this->dispatch('toast', message: 'Question not found', type: 'error');
            return;
        }

        $this->selectedQuestion = $question;
        $this->name = $question->name;
        $this->questionText = $question->question_text;
        $this->questionTypeId = $question->question_type_id;
        $this->options = $question->options ?: [''];
        $this->isRequired = $question->is_required;
        $this->isActive = $question->is_active;
        $this->showEditModal = true;
    }

    public function updateQuestion()
    {
        $this->validate();

        try {
            // Filter out empty options
            $filteredOptions = array_filter($this->options, function($option) {
                return !empty(trim($option));
            });

            $this->selectedQuestion->update([
                'name' => $this->name,
                'question_text' => $this->questionText,
                'question_type_id' => $this->questionTypeId,
                'options' => !empty($filteredOptions) ? array_values($filteredOptions) : null,
                'is_required' => $this->isRequired,
                'is_active' => $this->isActive,
            ]);

            $this->dispatch('toast', message: 'Question updated successfully!', type: 'success');
            $this->resetForm();
            $this->showEditModal = false;

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Error updating question: ' . $e->getMessage(), type: 'error');
        }
    }

    public function confirmDelete($questionId)
    {
        $this->selectedQuestion = Question::find($questionId);
        $this->showDeleteModal = true;
    }

    public function deleteQuestion()
    {
        try {
            $this->selectedQuestion->delete();
            $this->dispatch('toast', message: 'Question deleted successfully!', type: 'success');
            $this->showDeleteModal = false;
            $this->selectedQuestion = null;

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Error deleting question: ' . $e->getMessage(), type: 'error');
        }
    }

    public function confirmBulkDelete()
    {
        if (empty($this->selectedQuestions)) {
            $this->dispatch('toast', message: 'Please select questions to delete', type: 'error');
            return;
        }

        $this->showBulkDeleteModal = true;
    }

    public function bulkDeleteQuestions()
    {
        try {
            Question::whereIn('id', $this->selectedQuestions)->delete();
            $this->dispatch('toast', message: count($this->selectedQuestions) . ' questions deleted successfully!', type: 'success');
            $this->selectedQuestions = [];
            $this->selectAll = false;
            $this->showBulkDeleteModal = false;

        } catch (\Exception $e) {
            $this->dispatch('toast', message: 'Error deleting questions: ' . $e->getMessage(), type: 'error');
        }
    }

    public function toggleQuestionSelection($questionId)
    {
        if (in_array($questionId, $this->selectedQuestions)) {
            $this->selectedQuestions = array_values(array_filter($this->selectedQuestions, function($id) use ($questionId) {
                return $id !== $questionId;
            }));
        } else {
            $this->selectedQuestions[] = $questionId;
        }

        // Ensure array is properly indexed
        $this->selectedQuestions = array_values($this->selectedQuestions);

        // Update select all checkbox based on current selection
        $totalQuestions = $this->getQuestions()->count();
        $this->selectAll = $totalQuestions > 0 && count($this->selectedQuestions) === $totalQuestions;
    }

    public function addOption()
    {
        $this->options[] = '';
    }

    public function removeOption($index)
    {
        if (count($this->options) > 1) {
            unset($this->options[$index]);
            $this->options = array_values($this->options); // Re-index array
        }
    }

    public function resetForm()
    {
        $this->name = '';
        $this->questionText = '';
        $this->questionTypeId = '';
        $this->options = [''];
        $this->isRequired = false;
        $this->isActive = true;
        $this->selectedQuestion = null;
    }

    public function closeModals()
    {
        $this->showCreateModal = false;
        $this->showEditModal = false;
        $this->showDeleteModal = false;
        $this->showBulkDeleteModal = false;
        $this->resetForm();
    }
}

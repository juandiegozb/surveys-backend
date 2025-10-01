<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class BulkAssignQuestionsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'survey_uuid' => 'required|string|exists:surveys,uuid',
            'question_uuids' => 'required|array|min:1',
            'question_uuids.*' => 'required|string|exists:questions,uuid',
            'settings' => 'nullable|array',
        ];
    }
}

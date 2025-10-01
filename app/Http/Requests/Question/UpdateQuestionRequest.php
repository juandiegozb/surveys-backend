<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'question_text' => 'sometimes|required|string|max:2000',
            'question_type_id' => 'sometimes|required|exists:question_types,id',
            'options' => 'nullable|array',
            'validation_rules' => 'nullable|array',
            'image_url' => 'nullable|url|max:1000',
            'attachments' => 'nullable|array',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ];
    }
}

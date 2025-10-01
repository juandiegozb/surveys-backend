<?php

namespace App\Http\Requests\Question;

use Illuminate\Foundation\Http\FormRequest;

class BulkDeleteQuestionsRequest extends FormRequest
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
            'question_uuids' => 'required|array|min:1',
            'question_uuids.*' => 'required|string|exists:questions,uuid',
        ];
    }
}

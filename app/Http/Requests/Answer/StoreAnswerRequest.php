<?php

namespace App\Http\Requests\Answer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class StoreAnswerRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $rules = [
            'respondent_id' => 'nullable|string|uuid',
            'responses' => 'required|array',
        ];

        // Add dynamic validation for each response
        if ($this->has('responses')) {
            foreach ($this->input('responses', []) as $questionUuid => $responseData) {
                $baseKey = "responses.{$questionUuid}";

                // Basic validation for response structure
                $rules["{$baseKey}.value"] = 'nullable|string';
                $rules["{$baseKey}.values"] = 'nullable|array';
                $rules["{$baseKey}.option_index"] = 'nullable|integer';
                $rules["{$baseKey}.option_indices"] = 'nullable|array';
                $rules["{$baseKey}.option_indices.*"] = 'integer';

                // File validation - check if this is a file upload
                if (isset($responseData['file']) && $responseData['file'] instanceof UploadedFile) {
                    $rules["{$baseKey}.file"] = [
                        'file',
                        'max:10240', // 10MB max
                        'mimes:jpg,jpeg,png,gif,pdf,doc,docx,txt,csv,xlsx,xls,zip,rar'
                    ];
                }
            }
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'responses.*.file.file' => 'The uploaded item must be a valid file.',
            'responses.*.file.max' => 'The file size cannot exceed 10MB.',
            'responses.*.file.mimes' => 'The file must be of type: jpg, jpeg, png, gif, pdf, doc, docx, txt, csv, xlsx, zip.',
            'responses.required' => 'Survey responses are required.',
            'responses.array' => 'Responses must be in the correct format.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic can be added here
            $responses = $this->input('responses', []);

            foreach ($responses as $questionUuid => $responseData) {
                // Validate UUID format
                if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $questionUuid)) {
                    $validator->errors()->add("responses.{$questionUuid}", 'Invalid question UUID format.');
                }
            }
        });
    }
}

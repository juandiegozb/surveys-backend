<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'question' => [
                'uuid' => $this->question->uuid,
                'name' => $this->question->name,
                'question_text' => $this->question->question_text,
                'type' => $this->question->questionType->name ?? 'text',
                'is_required' => $this->question->is_required,
                'options' => $this->question->options,
            ],
            'answer' => $this->getProcessedAnswer(),
            'answer_text' => $this->answer_text,
            'answer_data' => $this->answer_data,
            'has_attachments' => $this->hasAttachments(),
            'attachments' => $this->getAllAttachments(),
            'respondent' => [
                'id' => $this->respondent_id,
                'type' => $this->respondent_type,
                'ip_address' => $this->when($request->user(), $this->ip_address), // Only show IP to authenticated users
            ],
            'metadata' => $this->metadata,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}

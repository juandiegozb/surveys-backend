<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'question_text' => $this->question_text,
            'question_type' => $this->whenLoaded('questionType', [
                'id' => $this->questionType->id,
                'name' => $this->questionType->name,
                'slug' => $this->questionType->slug ?? $this->questionType->name,
            ], [
                'id' => $this->question_type_id,
                'name' => 'Unknown',
                'slug' => 'unknown',
            ]),
            'options' => $this->options,
            'validation_rules' => $this->validation_rules,
            'image_url' => $this->image_url,
            'is_required' => $this->is_required,
            'is_active' => $this->is_active,
            'usage_count' => $this->usage_count,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'pivot' => $this->when($this->pivot, [
                'order' => $this->pivot?->order,
                'is_active' => (bool) $this->pivot?->is_active,
                'survey_specific_settings' => $this->pivot?->survey_specific_settings,
            ]),
        ];
    }
}

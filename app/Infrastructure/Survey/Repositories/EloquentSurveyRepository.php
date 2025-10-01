<?php

namespace App\Infrastructure\Survey\Repositories;

use App\Domain\Survey\Entities\Survey;
use App\Domain\Survey\Repositories\SurveyRepositoryInterface;
use App\Domain\Survey\ValueObjects\SurveyName;
use App\Domain\Survey\ValueObjects\SurveyStatus;
use App\Domain\Shared\ValueObjects\Uuid;
use App\Domain\Shared\ValueObjects\UserId;
use App\Models\Survey as EloquentSurvey;

class EloquentSurveyRepository implements SurveyRepositoryInterface
{
    public function save(Survey $survey): void
    {
        $eloquentSurvey = EloquentSurvey::where('uuid', $survey->getId()->getValue())->first();

        if (!$eloquentSurvey) {
            $eloquentSurvey = new EloquentSurvey();
            $eloquentSurvey->uuid = $survey->getId()->getValue();
        }

        $eloquentSurvey->name = $survey->getName()->getValue();
        $eloquentSurvey->description = $survey->getDescription();
        $eloquentSurvey->status = $survey->getStatus()->getValue();
        $eloquentSurvey->user_id = $survey->getUserId()->getValue();
        $eloquentSurvey->question_count = $survey->getQuestionCount();
        $eloquentSurvey->response_count = $survey->getResponseCount();

        $eloquentSurvey->save();
    }

    public function findById(Uuid $id): ?Survey
    {
        $eloquentSurvey = EloquentSurvey::where('uuid', $id->getValue())->first();

        if (!$eloquentSurvey) {
            return null;
        }

        return $this->mapToDomainEntity($eloquentSurvey);
    }

    public function findByUserId(UserId $userId): array
    {
        $eloquentSurveys = EloquentSurvey::where('user_id', $userId->getValue())->get();

        return $eloquentSurveys->map(function ($eloquentSurvey) {
            return $this->mapToDomainEntity($eloquentSurvey);
        })->toArray();
    }

    public function delete(Uuid $id): void
    {
        EloquentSurvey::where('uuid', $id->getValue())->delete();
    }

    public function nextIdentity(): Uuid
    {
        return Uuid::generate();
    }

    private function mapToDomainEntity(EloquentSurvey $eloquentSurvey): Survey
    {
        // Crear la entidad de dominio usando reflexión ya que el constructor es privado
        $reflection = new \ReflectionClass(Survey::class);
        $survey = $reflection->newInstanceWithoutConstructor();

        // Establecer propiedades privadas usando reflexión
        $this->setPrivateProperty($survey, 'id', Uuid::fromString($eloquentSurvey->uuid));
        $this->setPrivateProperty($survey, 'name', new SurveyName($eloquentSurvey->name));
        $this->setPrivateProperty($survey, 'description', $eloquentSurvey->description);
        $this->setPrivateProperty($survey, 'status', new SurveyStatus($eloquentSurvey->status));
        $this->setPrivateProperty($survey, 'userId', new UserId($eloquentSurvey->user_id));
        $this->setPrivateProperty($survey, 'questionCount', $eloquentSurvey->question_count);
        $this->setPrivateProperty($survey, 'responseCount', $eloquentSurvey->response_count);
        $this->setPrivateProperty($survey, 'createdAt', $eloquentSurvey->created_at);
        $this->setPrivateProperty($survey, 'updatedAt', $eloquentSurvey->updated_at);
        $this->setPrivateProperty($survey, 'domainEvents', []);

        return $survey;
    }

    private function setPrivateProperty($object, string $propertyName, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}

<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Infrastructure\Persistence;

use Dbapi\Forms\Form\Domain\Form;
use Dbapi\Forms\Form\Domain\FormId;
use Dbapi\Forms\Form\Domain\FormKey;
use Dbapi\Forms\Form\Domain\FormRepository;
use Dbapi\Forms\Form\Domain\FormSubmission;
use Dba\DddSkeleton\Shared\Domain\Bus\Event\EventBus;
use Dba\DddSkeleton\Shared\Infrastructure\Persistence\Eloquent\EloquentRepository;
use Illuminate\Database\Eloquent\Model;

final class EloquentFormRepository extends EloquentRepository implements FormRepository
{
    public function __construct(Model $model, EventBus $eventBus)
    {
        parent::__construct($model, $eventBus);
    }

    public function save(Form $form): Form
    {
        $primitives = $form->toPrimitives();

        $saved = $this->model->updateOrCreate(
            ['key' => $primitives['key']],
            [
                'name' => $primitives['name'],
                'recipient_email' => $primitives['recipient_email'],
                'fields' => $primitives['fields'],
                'active' => $primitives['active'],
            ]
        );

        $this->publishEvents($form);

        return Form::fromPrimitives($saved->toArray());
    }

    public function search(FormId $id): ?Form
    {
        $model = $this->model->find($id->value());

        return $model ? Form::fromPrimitives($model->toArray()) : null;
    }

    public function searchByKey(FormKey $key): ?Form
    {
        $model = $this->model->where('key', $key->value())->first();

        return $model ? Form::fromPrimitives($model->toArray()) : null;
    }

    public function saveSubmission(FormSubmission $submission, ?Form $form = null): void
    {
        $submissionModel = new \App\Models\FormSubmission();
        $submissionModel->create($submission->toPrimitives());

        if ($form !== null) {
            $this->publishEvents($form);
        }
    }
}

<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Application\Submit;

use App\Jobs\SendToCrmJob;
use Dbapi\Forms\Form\Domain\FormInactiveException;
use Dbapi\Forms\Form\Domain\FormKey;
use Dbapi\Forms\Form\Domain\FormNotFoundException;
use Dbapi\Forms\Form\Domain\FormRepository;
use Dbapi\Forms\Form\Domain\FormSubmission;
use Dbapi\Forms\Form\Domain\FormValidationFailedException;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;
use Illuminate\Support\Facades\Validator;

final class SubmitFormCommandHandler implements CommandHandler
{
    public function __construct(private readonly FormRepository $repository) {}

    public function __invoke(SubmitFormCommand $command): void
    {
        $form = $this->repository->searchByKey(new FormKey($command->key()));

        if ($form === null || $form->id() === null) {
            throw FormNotFoundException::forKey($command->key());
        }

        if ($form->active() === false) {
            throw FormInactiveException::forKey($command->key());
        }

        $rules = [];
        foreach ($form->fields() as $field) {
            $fieldRules = [];
            $fieldRules[] = $field->required() ? 'required' : 'nullable';

            $fieldRules[] = match ($field->type()) {
                'email' => 'email',
                'checkbox' => 'boolean',
                default => 'string',
            };

            $rules[$field->name()] = $fieldRules;
        }

        $validator = Validator::make($command->data(), $rules);
        if ($validator->fails()) {
            throw new FormValidationFailedException(
                $validator->errors()->first(),
                $validator->errors()->toArray(),
            );
        }

        $submission = new FormSubmission(
            formId: $form->id()->value(),
            data: $command->data(),
            ipAddress: $command->ipAddress(),
            userAgent: $command->userAgent(),
        );

        $form->recordSubmission();
        $this->repository->saveSubmission($submission, $form);

        SendToCrmJob::dispatch(
            $form->key()->value(),
            $command->data(),
            $form->recipientEmail()?->value(),
        );
    }
}

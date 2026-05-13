<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Application\Create;

use Dbapi\Forms\Form\Domain\Form;
use Dbapi\Forms\Form\Domain\FormField;
use Dbapi\Forms\Form\Domain\FormKey;
use Dbapi\Forms\Form\Domain\FormName;
use Dbapi\Forms\Form\Domain\FormRecipientEmail;
use Dbapi\Forms\Form\Domain\FormRepository;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandHandler;

final class CreateFormCommandHandler implements CommandHandler
{
    public function __construct(
        private readonly FormRepository $repository,
        private readonly CreatedFormIdHolder $idHolder,
    ) {}

    public function __invoke(CreateFormCommand $command): void
    {
        $fields = array_map(
            static fn (array $field) => FormField::fromArray($field),
            $command->fields()
        );

        $form = Form::create(
            new FormKey($command->key()),
            new FormName($command->name()),
            $command->recipientEmail() !== null ? new FormRecipientEmail($command->recipientEmail()) : null,
            $fields,
            $command->active(),
        );

        $persisted = $this->repository->save($form);

        $this->idHolder->set($persisted->id()?->value());
    }
}

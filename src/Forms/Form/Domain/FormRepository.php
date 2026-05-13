<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Domain;

interface FormRepository
{
    /** Persists the form and returns it rehydrated with the assigned id. */
    public function save(Form $form): Form;

    public function search(FormId $id): ?Form;

    public function searchByKey(FormKey $key): ?Form;

    public function saveSubmission(FormSubmission $submission, ?Form $form = null): void;
}

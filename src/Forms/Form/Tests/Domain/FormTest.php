<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Tests\Domain;

use Dbapi\Forms\Form\Domain\Form;
use Dbapi\Forms\Form\Domain\FormCreatedDomainEvent;
use Dbapi\Forms\Form\Domain\FormField;
use Dbapi\Forms\Form\Domain\FormKey;
use Dbapi\Forms\Form\Domain\FormName;
use Dbapi\Forms\Form\Domain\FormRecipientEmail;
use Dbapi\Forms\Form\Domain\FormSubmittedDomainEvent;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FormTest extends TestCase
{
    #[Test]
    public function it_records_a_form_created_event_on_creation(): void
    {
        $form = Form::create(
            new FormKey('contact-us'),
            new FormName('Contact Us'),
            new FormRecipientEmail('a@b.com'),
            [new FormField('email', 'Email', 'email', true)],
            true,
        );

        $events = $form->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(FormCreatedDomainEvent::class, $events[0]);
        $this->assertSame('contact-us', $events[0]->key());
        $this->assertSame('Contact Us', $events[0]->name());
    }

    #[Test]
    public function it_records_a_submitted_event_when_recordSubmission_is_called(): void
    {
        $form = Form::create(
            new FormKey('contact-us'),
            new FormName('Contact'),
            null,
            [new FormField('email', 'Email', 'email', true)],
            true,
        );
        $form->pullDomainEvents();

        $form->recordSubmission();

        $events = $form->pullDomainEvents();
        $this->assertCount(1, $events);
        $this->assertInstanceOf(FormSubmittedDomainEvent::class, $events[0]);
        $this->assertSame('contact-us', $events[0]->key());
    }

    #[Test]
    public function form_key_value_object_rejects_invalid_strings(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FormKey('Not Valid Slug!');
    }

    #[Test]
    public function form_recipient_email_value_object_rejects_invalid_emails(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new FormRecipientEmail('not-an-email');
    }
}

<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendToCrmJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $formKey,
        private readonly array $data,
        private readonly ?string $recipientEmail,
    ) {}

    public function handle(): void
    {
        // Placeholder integration point for TermoCRM forwarding.
        Log::info('SendToCrmJob processed', [
            'form_key' => $this->formKey,
            'recipient_email' => $this->recipientEmail,
            'payload_keys' => array_keys($this->data),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Infrastructure\SpamProtection;

use Illuminate\Support\Facades\Cache;

final class SpamProtection
{
    /**
     * The hidden field name documented in OpenAPI. Front-end clients that follow
     * the docs send `honeypot`; the legacy `_hp` field is accepted for backwards
     * compatibility with the previous implementation.
     */
    private const HONEYPOT_FIELDS = ['honeypot', '_hp'];

    public function assertNotSpam(string $formKey, ?string $ip, array $payload): void
    {
        foreach (self::HONEYPOT_FIELDS as $field) {
            if (!empty($payload[$field] ?? null)) {
                throw SpamDetectedException::honeypotFilled();
            }
        }

        // Simple throttle: same IP + form key within 5 seconds
        if ($ip !== null) {
            $cacheKey = 'forms:submit:' . $formKey . ':' . sha1($ip);
            if (Cache::has($cacheKey)) {
                throw TooManySubmissionsException::forFormAndIp();
            }
            Cache::put($cacheKey, true, now()->addSeconds(5));
        }
    }
}

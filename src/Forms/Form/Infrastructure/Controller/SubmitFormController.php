<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Infrastructure\Controller;

use Dbapi\Forms\Form\Application\Submit\SubmitFormCommand;
use Dbapi\Forms\Form\Domain\FormInactiveException;
use Dbapi\Forms\Form\Domain\FormNotFoundException;
use Dbapi\Forms\Form\Domain\FormValidationFailedException;
use Dbapi\Forms\Form\Infrastructure\SpamProtection\SpamProtection;
use Dbapi\Forms\Form\Infrastructure\SpamProtection\SpamDetectedException;
use Dbapi\Forms\Form\Infrastructure\SpamProtection\TooManySubmissionsException;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class SubmitFormController extends ApiController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly SpamProtection $spamProtection,
    ) {}

    #[OA\Post(
        path: "/{tenant}/{version}/forms/{key}/submit",
        summary: "Submit form data",
        description: "Submit form data to a published form. Public endpoint. Subject to anti-spam checks (honeypot + rate limiting 5 per 60s per IP).",
        tags: ["Forms"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "key", in: "path", required: true, schema: new OA\Schema(type: "string"), description: "Form key/slug"),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Form submission data — keys/values match form field definitions. Include the empty 'honeypot' field for anti-spam.",
            content: new OA\JsonContent(
                example: [
                    "name" => "John Doe",
                    "email" => "john@example.com",
                    "message" => "Hello, I have a question",
                    "honeypot" => "",
                ]
            )
        ),
        responses: [
            new OA\Response(response: 202, description: "Form submitted (async processing)"),
            new OA\Response(response: 403, description: "Spam detected or rate limit exceeded or form inactive"),
            new OA\Response(response: 404, description: "Form not found"),
            new OA\Response(response: 422, description: "Validation failed or missing required fields"),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $key = (string) $request->route('key');
        $payload = $request->all();

        try {
            $this->spamProtection->assertNotSpam(
                $key,
                $request->ip(),
                $payload,
            );

            $this->bus->dispatch(new SubmitFormCommand(
                key: $key,
                data: $payload,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            ));
        } catch (FormNotFoundException $e) {
            return $this->sendError($e->getMessage(), [], 404);
        } catch (FormInactiveException $e) {
            return $this->sendError($e->getMessage(), [], 403);
        } catch (FormValidationFailedException $e) {
            return $this->sendError($e->getMessage(), $e->errors(), 422);
        } catch (SpamDetectedException | TooManySubmissionsException $e) {
            return $this->sendError($e->getMessage(), [], 403);
        }

        return $this->sendResponse(null, 'Form submitted successfully')->setStatusCode(202);
    }
}

<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Infrastructure\Controller;

use Dbapi\Forms\Form\Application\Create\CreatedFormIdHolder;
use Dbapi\Forms\Form\Application\Create\CreateFormCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CreateFormController extends ApiController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly CreatedFormIdHolder $idHolder,
    ) {}

    #[OA\Post(
        path: "/{tenant}/{version}/forms",
        summary: "Create a form definition",
        description: "Create a new form with dynamic field definitions. Returns the persisted form id on success.",
        security: [["bearerAuth" => []]],
        tags: ["Forms"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), description: "Tenant app_id"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), description: "API version"),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name", "key", "fields"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Contact Us", description: "Form display name"),
                    new OA\Property(property: "key", type: "string", example: "contact-us", description: "URL slug (lowercase alphanumeric and hyphens)"),
                    new OA\Property(property: "recipient_email", type: "string", format: "email", nullable: true, description: "Email to receive submissions"),
                    new OA\Property(property: "active", type: "boolean", example: true, description: "Accept submissions"),
                    new OA\Property(
                        property: "fields",
                        type: "array",
                        description: "Form fields definition",
                        items: new OA\Items(
                            required: ["name", "label", "type", "required"],
                            properties: [
                                new OA\Property(property: "name", type: "string", example: "email", description: "Field variable name (snake_case)"),
                                new OA\Property(property: "label", type: "string", example: "Email Address"),
                                new OA\Property(property: "type", type: "string", enum: ["text", "email", "tel", "textarea", "select", "checkbox"]),
                                new OA\Property(property: "required", type: "boolean", example: true),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Form created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation failed"),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/'],
            'recipient_email' => ['nullable', 'email'],
            'active' => ['sometimes', 'boolean'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.name' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9_]+$/'],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.type' => ['required', 'in:text,email,tel,textarea,select,checkbox'],
            'fields.*.required' => ['required', 'boolean'],
        ]);

        $this->bus->dispatch(new CreateFormCommand(
            name: $validated['name'],
            key: $validated['key'],
            recipientEmail: $validated['recipient_email'] ?? null,
            active: (bool) ($validated['active'] ?? true),
            fields: $validated['fields'],
        ));

        return $this->sendResponse(
            ['id' => $this->idHolder->id()],
            'Form created successfully'
        )->setStatusCode(201);
    }
}

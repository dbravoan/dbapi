<?php

declare(strict_types=1);

namespace Dbapi\Language\Language\Infrastructure\Controller;

use Dbapi\Language\Language\Application\Create\CreateLanguageCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CreateLanguageController extends ApiController
{
    private CommandBus $bus;

    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    #[OA\Post(
        path: "/{tenant}/{version}/languages",
        summary: "Register a language",
        description: "Create a new language definition for translatable content. Language codes must be ISO 639-1 (2-letter codes like 'en', 'es', 'fr').",
        security: [["bearerAuth" => []]],
        tags: ["Languages"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["id", "code", "name", "native_name"],
                properties: [
                    new OA\Property(property: "id", type: "string", format: "uuid", description: "Unique language identifier"),
                    new OA\Property(property: "code", type: "string", example: "en", description: "ISO 639-1 2-letter code (lowercase)"),
                    new OA\Property(property: "name", type: "string", example: "English", description: "Language name in English"),
                    new OA\Property(property: "native_name", type: "string", example: "English", description: "Language name in its native language"),
                    new OA\Property(property: "is_default", type: "boolean", example: false, description: "Set as tenant's default language"),
                    new OA\Property(property: "is_active", type: "boolean", example: true, description: "Available for translations"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Language created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error (invalid code format or duplicate)")
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id'          => ['required', 'uuid'],
            'code'        => ['required', 'string', 'size:2', 'regex:/^[a-z]{2}$/'],
            'name'        => ['required', 'string', 'max:100'],
            'native_name' => ['required', 'string', 'max:100'],
            'is_default'  => ['sometimes', 'boolean'],
            'is_active'   => ['sometimes', 'boolean'],
        ]);

        $this->bus->dispatch(new CreateLanguageCommand(
            $validated['id'],
            $validated['code'],
            $validated['name'],
            $validated['native_name'],
            (bool) ($validated['is_default'] ?? false),
            (bool) ($validated['is_active'] ?? true),
        ));

        return $this->sendResponse(null, 'Language created successfully')->setStatusCode(201);
    }
}

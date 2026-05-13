<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Infrastructure\Controller;

use Dbapi\Blogging\Post\Application\Create\CreatePostCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CreatePostController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(
        path: "/{tenant}/{version}/posts",
        summary: "Crear un post de blog",
        security: [["bearerAuth" => []]],
        tags: ["Blogging - Posts"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["id", "title", "content", "language"],
                properties: [
                    new OA\Property(property: "id", type: "string", format: "uuid", example: "550e8400-e29b-41d4-a716-446655440000"),
                    new OA\Property(property: "title", type: "string", maxLength: 255, example: "Mi primer post con IA"),
                    new OA\Property(property: "content", type: "string", example: "La inteligencia artificial es increíble..."),
                    new OA\Property(property: "language", type: "string", maxLength: 5, example: "es"),
                    new OA\Property(property: "seo_title", type: "string", maxLength: 60, nullable: true),
                    new OA\Property(property: "seo_description", type: "string", maxLength: 160, nullable: true),
                    new OA\Property(property: "seo_keywords", type: "string", nullable: true),
                    new OA\Property(property: "canonical_url", type: "string", format: "url", nullable: true),
                    new OA\Property(property: "og_title", type: "string", maxLength: 95, nullable: true),
                    new OA\Property(property: "og_description", type: "string", maxLength: 200, nullable: true),
                    new OA\Property(property: "og_image", type: "string", format: "url", nullable: true),
                    new OA\Property(property: "category_id", type: "string", format: "uuid", nullable: true, example: null),
                    new OA\Property(
                        property: "tag_names",
                        type: "array",
                        items: new OA\Items(type: "string"),
                        example: ["php", "laravel", "ia"]
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Post creado correctamente",
                content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")
            ),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Error de validación", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'language' => ['required', 'string', 'max:5'],
            'seo_title' => ['nullable', 'string', 'max:60'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url'],
            'og_title' => ['nullable', 'string', 'max:95'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'og_image' => ['nullable', 'url'],
            'category_id' => ['nullable', 'uuid'],
            'tag_names' => ['sometimes', 'array'],
            'tag_names.*' => ['string', 'max:100'],
        ]);

        $command = new CreatePostCommand(
            $validated['id'],
            $validated['title'],
            $validated['content'],
            $validated['language'],
            $validated['seo_title'] ?? null,
            $validated['seo_description'] ?? null,
            $validated['seo_keywords'] ?? null,
            $validated['canonical_url'] ?? null,
            $validated['og_title'] ?? null,
            $validated['og_description'] ?? null,
            $validated['og_image'] ?? null,
            $validated['category_id'] ?? null,
            $validated['tag_names'] ?? []
        );

        $this->bus->dispatch($command);

        return $this->sendResponse(null, 'Post created successfully')->setStatusCode(201);
    }
}

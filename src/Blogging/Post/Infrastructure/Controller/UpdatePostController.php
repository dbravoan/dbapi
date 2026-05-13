<?php

declare(strict_types=1);

namespace Dbapi\Blogging\Post\Infrastructure\Controller;

use Dbapi\Blogging\Post\Application\Update\UpdatePostCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class UpdatePostController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Put(
        path: "/{tenant}/{version}/posts/{id}",
        summary: "Actualizar un post",
        security: [["bearerAuth" => []]],
        tags: ["Blogging - Posts"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["language"],
                properties: [
                    new OA\Property(property: "language", type: "string", maxLength: 5, example: "es"),
                    new OA\Property(property: "title", type: "string", maxLength: 255, nullable: true),
                    new OA\Property(property: "content", type: "string", nullable: true),
                    new OA\Property(property: "seo_title", type: "string", maxLength: 60, nullable: true),
                    new OA\Property(property: "seo_description", type: "string", maxLength: 160, nullable: true),
                    new OA\Property(property: "seo_keywords", type: "string", nullable: true),
                    new OA\Property(property: "canonical_url", type: "string", format: "url", nullable: true),
                    new OA\Property(property: "og_title", type: "string", maxLength: 95, nullable: true),
                    new OA\Property(property: "og_description", type: "string", maxLength: 200, nullable: true),
                    new OA\Property(property: "og_image", type: "string", format: "url", nullable: true),
                    new OA\Property(property: "category_id", type: "string", format: "uuid", nullable: true),
                    new OA\Property(property: "tag_names", type: "array", items: new OA\Items(type: "string"), nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Post actualizado", content: new OA\JsonContent(ref: "#/components/schemas/SuccessResponse")),
            new OA\Response(response: 401, description: "No autenticado", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Error de validación", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(string $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'language' => ['required', 'string', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:60'],
            'seo_description' => ['nullable', 'string', 'max:160'],
            'seo_keywords' => ['nullable', 'string', 'max:255'],
            'canonical_url' => ['nullable', 'url'],
            'og_title' => ['nullable', 'string', 'max:95'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'og_image' => ['nullable', 'url'],
            'category_id' => ['nullable', 'uuid'],
            'tag_names' => ['nullable', 'array'],
            'tag_names.*' => ['string', 'max:100'],
        ]);

        $command = new UpdatePostCommand(
            $id,
            $validated['language'],
            $validated['title'] ?? null,
            $validated['content'] ?? null,
            $validated['seo_title'] ?? null,
            $validated['seo_description'] ?? null,
            $validated['seo_keywords'] ?? null,
            $validated['canonical_url'] ?? null,
            $validated['og_title'] ?? null,
            $validated['og_description'] ?? null,
            $validated['og_image'] ?? null,
            $validated['category_id'] ?? null,
            $validated['tag_names'] ?? null,
        );

        $this->bus->dispatch($command);

        return $this->sendResponse(null, 'Post updated successfully');
    }
}

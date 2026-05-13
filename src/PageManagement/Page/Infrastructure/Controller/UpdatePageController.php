<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Infrastructure\Controller;

use Dbapi\PageManagement\Page\Application\Update\UpdatePageCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class UpdatePageController extends ApiController
{
    private CommandBus $bus;

    public function __construct(CommandBus $bus)
    {
        $this->bus = $bus;
    }

    #[OA\Put(
        path: "/{tenant}/{version}/pages/{id}",
        summary: "Update a page or add/update a translation",
        description: "Update page content for a specific language. If translation exists, updates it; if not, creates new translation. Can also update page status (core field).",
        security: [["bearerAuth" => []]],
        tags: ["Pages"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "string", format: "uuid")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["language_code"],
                properties: [
                    new OA\Property(property: "language_code", type: "string", example: "es"),
                    new OA\Property(property: "status", type: "string", enum: ["draft", "published", "archived"], nullable: true),
                    new OA\Property(property: "slug", type: "string", nullable: true),
                    new OA\Property(property: "title", type: "string", nullable: true),
                    new OA\Property(property: "content", type: "array", nullable: true, items: new OA\Items(type: "object")),
                    new OA\Property(property: "seo_title", type: "string", nullable: true),
                    new OA\Property(property: "seo_description", type: "string", nullable: true),
                    new OA\Property(property: "seo_keywords", type: "string", nullable: true),
                    new OA\Property(property: "canonical_url", type: "string", format: "url", nullable: true),
                    new OA\Property(property: "og_title", type: "string", nullable: true),
                    new OA\Property(property: "og_description", type: "string", nullable: true),
                    new OA\Property(property: "og_image", type: "string", format: "url", nullable: true),
                    new OA\Property(property: "structured_data", type: "object", nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Page updated"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/ErrorResponse")),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = (string) $request->route('id');

        $validated = $request->validate([
            'language_code'  => ['required', 'string', 'size:2'],
            'status'         => ['nullable', 'string', 'in:draft,published,archived'],
            'slug'           => ['nullable', 'string', 'max:255'],
            'title'          => ['nullable', 'string', 'max:255'],
            'content'        => ['nullable', 'array'],
            'seo_title'      => ['nullable', 'string', 'max:60'],
            'seo_description'=> ['nullable', 'string', 'max:160'],
            'seo_keywords'   => ['nullable', 'string', 'max:255'],
            'canonical_url'  => ['nullable', 'url'],
            'og_title'       => ['nullable', 'string', 'max:95'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'og_image'       => ['nullable', 'url'],
            'structured_data'=> ['nullable', 'array'],
        ]);

        $this->bus->dispatch(new UpdatePageCommand(
            id:             $id,
            status:         $validated['status'] ?? null,
            languageCode:   $validated['language_code'],
            slug:           $validated['slug'] ?? null,
            title:          $validated['title'] ?? null,
            content:        $validated['content'] ?? null,
            seoTitle:       $validated['seo_title'] ?? null,
            seoDescription: $validated['seo_description'] ?? null,
            seoKeywords:    $validated['seo_keywords'] ?? null,
            canonicalUrl:   $validated['canonical_url'] ?? null,
            ogTitle:        $validated['og_title'] ?? null,
            ogDescription:  $validated['og_description'] ?? null,
            ogImage:        $validated['og_image'] ?? null,
            structuredData: $validated['structured_data'] ?? null,
        ));

        return $this->sendResponse(null, 'Page updated successfully');
    }
}

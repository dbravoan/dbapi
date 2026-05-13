<?php

declare(strict_types=1);

namespace Dbapi\PageManagement\Page\Infrastructure\Controller;

use Dbapi\PageManagement\Page\Application\Create\CreatePageCommand;
use Dba\DddSkeleton\Shared\Domain\Bus\Command\CommandBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class CreatePageController extends ApiController
{
    public function __construct(private readonly CommandBus $bus) {}

    #[OA\Post(
        path: "/{tenant}/{version}/pages",
        summary: "Create a page with its first language translation",
        description: "Create a new page with block editor content and SEO metadata. Content must follow block editor schema (rows/columns/blocks). Each translation is stored in intermediate table per language.",
        security: [["bearerAuth" => []]],
        tags: ["Pages"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "acme"),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string"), example: "v1"),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            description: "Page data with translatable fields. Content is block editor JSON.",
            content: new OA\JsonContent(
                required: ["id", "status", "language_code", "slug", "title", "content"],
                properties: [
                    new OA\Property(property: "id", type: "string", format: "uuid", description: "Unique page identifier"),
                    new OA\Property(property: "status", type: "string", enum: ["draft", "published", "archived"], example: "published", description: "Publication status"),
                    new OA\Property(property: "language_code", type: "string", example: "en", description: "ISO 639-1 language code"),
                    new OA\Property(property: "slug", type: "string", example: "about-us", description: "URL-friendly slug (unique per language)"),
                    new OA\Property(property: "title", type: "string", example: "About Us", description: "Page title"),
                    new OA\Property(
                        property: "content",
                        type: "array",
                        description: "Block editor content (rows -> columns -> blocks)",
                        items: new OA\Items(type: "object")
                    ),
                    new OA\Property(property: "seo_title", type: "string", nullable: true, maxLength: 60),
                    new OA\Property(property: "seo_description", type: "string", nullable: true, maxLength: 160),
                    new OA\Property(property: "seo_keywords", type: "string", nullable: true),
                    new OA\Property(property: "canonical_url", type: "string", format: "url", nullable: true),
                    new OA\Property(property: "og_title", type: "string", nullable: true, maxLength: 95),
                    new OA\Property(property: "og_description", type: "string", nullable: true, maxLength: 200),
                    new OA\Property(property: "og_image", type: "string", format: "url", nullable: true),
                    new OA\Property(property: "structured_data", type: "object", nullable: true, description: "JSON-LD structured data"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Page created successfully"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error (invalid content schema or language)"),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id'             => ['required', 'uuid'],
            'status'         => ['required', 'string', 'in:draft,published,archived'],
            'language_code'  => ['required', 'string', 'size:2'],
            'slug'           => ['required', 'string', 'max:255'],
            'title'          => ['required', 'string', 'max:255'],
            'content'        => ['required', 'array'],
            'seo_title'      => ['nullable', 'string', 'max:60'],
            'seo_description'=> ['nullable', 'string', 'max:160'],
            'seo_keywords'   => ['nullable', 'string', 'max:255'],
            'canonical_url'  => ['nullable', 'url'],
            'og_title'       => ['nullable', 'string', 'max:95'],
            'og_description' => ['nullable', 'string', 'max:200'],
            'og_image'       => ['nullable', 'url'],
            'structured_data'=> ['nullable', 'array'],
        ]);

        $this->bus->dispatch(new CreatePageCommand(
            id:             $validated['id'],
            status:         $validated['status'],
            languageCode:   $validated['language_code'],
            slug:           $validated['slug'],
            title:          $validated['title'],
            content:        $validated['content'],
            seoTitle:       $validated['seo_title'] ?? null,
            seoDescription: $validated['seo_description'] ?? null,
            seoKeywords:    $validated['seo_keywords'] ?? null,
            canonicalUrl:   $validated['canonical_url'] ?? null,
            ogTitle:        $validated['og_title'] ?? null,
            ogDescription:  $validated['og_description'] ?? null,
            ogImage:        $validated['og_image'] ?? null,
            structuredData: $validated['structured_data'] ?? null,
        ));

        return $this->sendResponse(null, 'Page created successfully')->setStatusCode(201);
    }
}

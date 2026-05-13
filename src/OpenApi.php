<?php

declare(strict_types=1);

namespace Dbapi;

use OpenApi\Attributes as OA;

/**
 * OpenAPI specification for DB API.
 *
 * Central definition file — every annotation here is merged with per-controller
 * annotations scanned by l5-swagger (scan paths: app/, src/).
 *
 * @see https://github.com/DarkaOnLine/L5-Swagger
 */
#[OA\Info(
    version: '1.0.0',
    title: 'DB API — Multitenant REST API',
    description: <<<DESC
Laravel 13 API with DDD/CQRS architecture and table-per-tenant multi-tenancy.

## Authentication
All write endpoints require a Bearer token issued by Laravel Passport.
Obtain one via `POST /oauth/token` (standard Passport password grant).

## Multitenancy
Every request must target a tenant via the URL `/tenant_id/v1/...`.
Tenant identifiers are 3-63 lowercase alphanumeric characters (hyphens/underscores allowed).

## Module gating
Some endpoints require the tenant to have a specific module enabled.
If a module is disabled, the API returns **403** with the message
`"Module 'X' is not enabled for this tenant"`.

## Standard response envelope
All endpoints return a consistent JSON envelope:
```json
{
  "success": true,
  "data": { ... },
  "message": "Operation completed successfully"
}
```
Errors replace `data` with `errors` (validation details).
DESC,
    contact: new OA\Contact(email: 'dev@example.com'),
)]
#[OA\Server(
    url: 'http://localhost',
    description: 'Local development'
)]
#[OA\Server(
    url: 'https://api.example.com',
    description: 'Production'
)]
#[OA\Tag(name: 'Blogging - Posts', description: 'Translatable blog posts with SEO, OG, categories and tags')]
#[OA\Tag(name: 'Blogging - Categories', description: 'Post categories (name only)')]
#[OA\Tag(name: 'Blogging - Tags', description: 'Post tags with auto-generated slugs')]
#[OA\Tag(name: 'Forms', description: 'Dynamic form definitions, public submissions, anti-spam (honeypot + rate-limit)')]
#[OA\Tag(name: 'Identity - Users', description: 'Cross-module user management (always available, no module gate)')]
#[OA\Tag(name: 'Languages', description: 'ISO 639-1 language catalog for translatable content')]
#[OA\Tag(name: 'Pages', description: 'Translatable pages with block editor content and full SEO/OG/JSON-LD metadata')]
#[OA\Tag(name: 'TodoList - Tasks', description: 'Simple tasks with status (pending/in_progress/done) and priority (low/medium/high)')]
#[OA\Tag(name: 'Tenant', description: 'Tenant management and provisioning (CLI commands, not HTTP)')]

// ─────────────────────────────────────────────────────────────────────────────
// Security schemes
// ─────────────────────────────────────────────────────────────────────────────
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Passport',
    description: 'Laravel Passport token. Obtain via POST /oauth/token'
)]

// ─────────────────────────────────────────────────────────────────────────────
// Shared schema components
// ─────────────────────────────────────────────────────────────────────────────

#[OA\Schema(
    schema: 'SuccessResponse',
    description: 'Standard success response envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', nullable: true, example: null),
        new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    description: 'Standard error response envelope',
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(property: 'errors', type: 'object', nullable: true,
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: ['title' => ['The title field is required.']]
        ),
    ],
    type: 'object'
)]

// ─────────────────────────────────────────────────────────────────────────────
// Controller response schemas (referenced by #[OA\Response] in controllers)
// ─────────────────────────────────────────────────────────────────────────────

#[OA\Schema(
    schema: 'PostResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(property: 'language', type: 'string', example: 'en'),
        new OA\Property(property: 'category', type: 'string'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'CategoryResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'TagResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'UserResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'TaskResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'done']),
        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high']),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'LanguageResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'code', type: 'string', example: 'en', description: 'ISO 639-1 language code'),
        new OA\Property(property: 'name', type: 'string', example: 'English'),
        new OA\Property(property: 'is_default', type: 'boolean', example: false),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'PageResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published']),
        new OA\Property(property: 'language_code', type: 'string', example: 'en'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'content', type: 'string', nullable: true, description: 'Block editor JSON or HTML'),
        new OA\Property(property: 'seo_title', type: 'string', nullable: true),
        new OA\Property(property: 'seo_description', type: 'string', nullable: true),
        new OA\Property(property: 'canonical_url', type: 'string', nullable: true),
        new OA\Property(property: 'og_title', type: 'string', nullable: true),
        new OA\Property(property: 'og_description', type: 'string', nullable: true),
        new OA\Property(property: 'og_image', type: 'string', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'FormResponse',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'key', type: 'string'),
        new OA\Property(property: 'recipient_email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'active', type: 'boolean'),
        new OA\Property(
            property: 'fields',
            type: 'array',
            items: new OA\Items(
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'label', type: 'string'),
                    new OA\Property(property: 'type', type: 'string', enum: ['text', 'email', 'tel', 'textarea', 'select', 'checkbox']),
                    new OA\Property(property: 'required', type: 'boolean'),
                ]
            )
        ),
    ],
    type: 'object'
)]

// ─────────────────────────────────────────────────────────────────────────────
// Entity schemas
// ─────────────────────────────────────────────────────────────────────────────

#[OA\Schema(
    schema: 'Post',
    description: 'Translatable blog post',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'content', type: 'string'),
        new OA\Property(property: 'language', type: 'string', example: 'en'),
        new OA\Property(property: 'category_id', type: 'string', format: 'uuid', nullable: true),
        new OA\Property(property: 'tag_names', type: 'array', items: new OA\Items(type: 'string')),
        new OA\Property(property: 'seo_title', type: 'string', nullable: true),
        new OA\Property(property: 'seo_description', type: 'string', nullable: true),
        new OA\Property(property: 'seo_keywords', type: 'string', nullable: true),
        new OA\Property(property: 'canonical_url', type: 'string', nullable: true),
        new OA\Property(property: 'og_title', type: 'string', nullable: true),
        new OA\Property(property: 'og_description', type: 'string', nullable: true),
        new OA\Property(property: 'og_image', type: 'string', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Category',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Tag',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'slug', type: 'string'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Form',
    description: 'Dynamic form definition',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'key', type: 'string', description: 'Unique form key used in public submission URL'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'recipient_email', type: 'string', format: 'email', nullable: true),
        new OA\Property(property: 'active', type: 'boolean'),
        new OA\Property(property: 'fields', type: 'array', items: new OA\Items(type: 'object'), description: 'JSON array of field definitions'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'name', type: 'string'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Language',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'code', type: 'string', example: 'en', description: 'ISO 639-1'),
        new OA\Property(property: 'name', type: 'string', example: 'English'),
        new OA\Property(property: 'native_name', type: 'string', example: 'English'),
        new OA\Property(property: 'is_default', type: 'boolean'),
        new OA\Property(property: 'is_active', type: 'boolean'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Page',
    description: 'Translatable page with block editor content',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'archived']),
        new OA\Property(property: 'language_code', type: 'string', example: 'en'),
        new OA\Property(property: 'slug', type: 'string'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'content', type: 'array', items: new OA\Items(type: 'object'), description: 'Block editor rows'),
        new OA\Property(property: 'seo_title', type: 'string', nullable: true),
        new OA\Property(property: 'seo_description', type: 'string', nullable: true),
        new OA\Property(property: 'seo_keywords', type: 'string', nullable: true),
        new OA\Property(property: 'canonical_url', type: 'string', nullable: true),
        new OA\Property(property: 'og_title', type: 'string', nullable: true),
        new OA\Property(property: 'og_description', type: 'string', nullable: true),
        new OA\Property(property: 'og_image', type: 'string', nullable: true),
        new OA\Property(property: 'structured_data', type: 'object', nullable: true, description: 'JSON-LD'),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'Task',
    properties: [
        new OA\Property(property: 'id', type: 'string', format: 'uuid'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['pending', 'in_progress', 'done']),
        new OA\Property(property: 'priority', type: 'string', enum: ['low', 'medium', 'high']),
        new OA\Property(property: 'description', type: 'string', nullable: true),
    ],
    type: 'object'
)]
class OpenApi
{
    /**
     * This class is never instantiated.
     * It exists solely to host OpenAPI attributes at the namespace level.
     */
}

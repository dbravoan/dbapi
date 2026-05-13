<?php

declare(strict_types=1);

namespace Dbapi\Forms\Form\Infrastructure\Controller;

use Dbapi\Forms\Form\Application\Find\FindFormQuery;
use Dbapi\Forms\Form\Application\Response\FormResponse;
use Dba\DddSkeleton\Shared\Domain\Bus\Query\QueryBus;
use Dba\DddSkeleton\Shared\Infrastructure\Laravel\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

final class FindFormController extends ApiController
{
    public function __construct(private readonly QueryBus $bus) {}

    #[OA\Get(
        path: "/{tenant}/{version}/forms/{id}",
        summary: "Get form definition",
        description: "Retrieve form schema including field definitions. Authenticated only.",
        security: [["bearerAuth" => []]],
        tags: ["Forms"],
        parameters: [
            new OA\Parameter(name: "tenant", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "version", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), description: "Form ID"),
        ],
        responses: [
            new OA\Response(response: 200, description: "Form found", content: new OA\JsonContent(ref: "#/components/schemas/FormResponse")),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Form not found"),
        ]
    )]
    public function __invoke(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');

        /** @var FormResponse|null $response */
        $response = $this->bus->ask(new FindFormQuery($id));

        if ($response === null) {
            return $this->sendError('Not Found', [], 404);
        }

        return $this->sendResponse($response->toArray(), 'Form found successfully');
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\QueryRequest;
use Symfony\Component\HttpFoundation\Response;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projects
    ) {}

    public function index(QueryRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 10);
        unset($filters['per_page']);

        $projects = $this->projects->list($filters, $perPage);

        return ProjectResource::collection($projects)->response();
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projects->findOrFail($id);

        return (new ProjectResource($project))->response();
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projects->create($request->mapped());

        return (new ProjectResource($project))->response()->setStatusCode(201);
    }

    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $project = $this->projects->update($id, $request->mapped());

        return (new ProjectResource($project))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $this->projects->delete($id);

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}

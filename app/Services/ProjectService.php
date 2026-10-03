<?php

namespace App\Services;

use App\Exceptions\ProjectNotFoundException;
use App\Models\Project;
use App\Repositories\ProjectRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use DB;
use Exception;

class ProjectService
{
    public function __construct(
        protected ProjectRepositoryInterface $projects
    ) {}

    public function list(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->projects->paginate($filters, $perPage);
    }

    public function findOrFail(int $id): Project
    {
        $project = $this->projects->find($id);

        if (!$project) {
            throw new ProjectNotFoundException($id);
        }

        return $project;
    }

    public function create(array $data): Project
    {
        try {
            return DB::transaction(fn() => $this->projects->create($data));
        
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Failed to create project', 0, $e);
        }
    }

    public function update(int $id, array $data): Project
    {
        try {
            $project = $this->findOrFail($id);
            return DB::transaction(fn() => $this->projects->update($project, $data));
        } catch (Exception $e) {
            if ($e instanceof ProjectNotFoundException) {
                throw $e;
            }
            throw new Exception('Failed to update project', 0, $e);
        }
    }

    public function delete(int $id): void
    {
        try {
            $project = $this->findOrFail($id);
            DB::transaction(fn() => $this->projects->delete($project));
        } catch (Exception $e) {
            if ($e instanceof ProjectNotFoundException) {
                throw $e;
            }
            throw new Exception('Failed to delete project', 0, $e);
        }
    }
}

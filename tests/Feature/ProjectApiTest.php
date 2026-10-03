<?php

namespace Tests\Feature;

use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ProjectApiTest extends TestCase
{
    use RefreshDatabase;

    protected function authenticatedUser(): User
    {
        return User::factory()->create();
    }

    protected function validPayload(array $overrides = [])
    {
        return array_merge([
            'clientName' => 'Test Corp',
            'projectName' => 'Website Revamp',
            'description' => 'Revamp the main website',
            'status' => 'Planning',
            'priority' => 'Medium',
            'startDate' => '2026-01-01',
            'dueDate' => '2026-02-01',
        ], $overrides);
    }

    public function test_guest_cannot_access_projects()
    {
        $this->getJson('/api/projects')->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_list_projects()
    {
        $user = $this->authenticatedUser();
        Project::factory()->count(3)->create();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(3, 'data');
    }

    public function test_create_a_project()
    {
        $user = $this->authenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/projects', $this->validPayload(['clientName' => 'Macme Corporation',]))
            ->assertStatus(Response::HTTP_CREATED)
            ->assertJsonPath('data.clientName', 'Macme Corporation')
            ->assertJsonPath('data.status', 'Planning')
            ->assertJsonPath('data.priority', 'Medium');

        $this->assertDatabaseHas('projects', ['client_name' => 'Macme Corporation']);

        $project = Project::firstOrFail();
        $this->assertInstanceOf(ProjectStatus::class, $project->status);
        $this->assertInstanceOf(ProjectPriority::class, $project->priority);
    }

    public function test_fails_to_create_project_with_bad_data()
    {
        $user = $this->authenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/projects', $this->validPayload([
                'clientName' => '',
                'status' => 'Unknown',
                'priority' => 'Urgent',
                'dueDate' => '2025-01-01',
            ]))
            ->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
            ->assertJsonValidationErrors(['clientName', 'status', 'priority', 'dueDate']);
    }

    public function test_show_a_project()
    {
        $user = $this->authenticatedUser();
        $project = Project::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/projects/{$project->id}")
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_it_doesnt_display_missing_project()
    {
        $user = $this->authenticatedUser();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects/999')
            ->assertStatus(Response::HTTP_NOT_FOUND);
    }

    public function test_update_a_project()
    {
        $user = $this->authenticatedUser();
        $project = Project::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/projects/{$project->id}", $this->validPayload([
                'projectName' => 'Updated Name',
            ]))
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.projectName', 'Updated Name');
        
        $this->assertDatabaseHas('projects', ['project_name' => 'Updated Name']);
    }

    public function test_delete_a_project()
    {
        $user = $this->authenticatedUser();
        $project = Project::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/projects/{$project->id}")
            ->assertStatus(Response::HTTP_NO_CONTENT);

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_filter_by_status()
    {
        $user = $this->authenticatedUser();
        Project::factory()->create(['status' => 'Completed']);
        Project::factory()->create(['status' => 'Planning']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects?status=Completed')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects?status=asdfsfsadf')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(0, 'data');
    }

    public function test_search_projects()
    {
        $user = $this->authenticatedUser();
        Project::factory()->create([
            'client_name' => 'Nova Fitness',
            'status' => 'In Progress',
            'priority' => 'High',
        ]);
        Project::factory()->create(['client_name' => 'Acme Corp']);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects?q=Nova')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data');

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/projects?q=Nova&status=In%20Progress&priority=High&per_page=1')
            ->assertStatus(Response::HTTP_OK)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1);
    }
}

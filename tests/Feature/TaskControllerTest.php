<?php

namespace Tests\Feature;

use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /* @test */
    public function test_it_can_list_all_tasks()
    {
        Task::factory()->count(3)->create();

        $response = $this->getJson('/tasks');

        $response->assertStatus(200)
            ->assertJsonCount(3)
            ->assertJsonStructure([
                '*' => ['id', 'title', 'description', 'status', 'created_at', 'updated_at']
            ]);
    }

    /* @test */
    public function test_it_can_show_a_specific_task()
    {
        $task = Task::factory()->create();

        $response = $this->getJson("/tasks/{$task->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $task->id,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status
            ]);
    }

    /* @test */
    public function test_it_returns_404_when_task_not_found()
    {
        $response = $this->getJson('/tasks/999');

        $response->assertStatus(404);
    }

    /* @test */
    public function test_it_can_create_a_new_task()
    {
        $taskData = [
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'status' => 'pending'
        ];

        $response = $this->postJson('/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJson($taskData);

        $this->assertDatabaseHas('tasks', $taskData);
    }

    /* @test */
    public function test_it_validates_required_fields_when_creating_task()
    {
        $response = $this->postJson('/tasks', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'description']);
    }

    /* @test */
    public function test_it_validates_status_enum_when_creating_task()
    {
        $taskData = [
            'title' => $this->faker->sentence,
            'status' => 'invalid_status'
        ];

        $response = $this->postJson('/tasks', $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /* @test */
    public function test_it_can_update_an_existing_task()
    {
        $task = Task::factory()->create(['status' => 'pending']);

        $updateData = [
            'title' => 'Updated Title',
            'status' => 'completed'
        ];

        $response = $this->putJson("/tasks/{$task->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson($updateData);

        $this->assertDatabaseHas('tasks', array_merge(['id' => $task->id], $updateData));
    }

    /* @test */
    public function test_it_validates_fields_when_updating_task()
    {
        $task = Task::factory()->create();

        $response = $this->putJson("/tasks/{$task->id}", [
            'status' => 'invalid_status'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    /* @test */
    public function test_it_returns_404_when_updating_nonexistent_task()
    {
        $response = $this->putJson('/tasks/999', [
            'title' => 'Updated Title'
        ]);

        $response->assertStatus(404);
    }

    /* @test */
    public function test_it_can_delete_a_task()
    {
        $task = Task::factory()->create();

        $response = $this->deleteJson("/tasks/{$task->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    /* @test */
    public function test_it_returns_404_when_deleting_nonexistent_task()
    {
        $response = $this->deleteJson('/tasks/999');

        $response->assertStatus(404);
    }

    /* @test */
    public function test_it_can_update_partial_fields()
    {
        $task = Task::factory()->create(['title' => 'Old Title', 'status' => 'pending']);

        $response = $this->putJson("/tasks/{$task->id}", [
            'title' => 'New Title'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'title' => 'New Title',
                'status' => 'pending'
            ]);
    }

    /* @test */
    public function test_it_returns_empty_list_when_no_tasks()
    {
        $response = $this->getJson('/tasks');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    /* @test */
    public function test_it_validates_title_max_length()
    {
        $taskData = [
            'title' => str_repeat('a', 256),
            'status' => 'pending'
        ];

        $response = $this->postJson('/tasks', $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    }

    /* @test */
    public function test_it_validates_description_is_string()
    {
        $taskData = [
            'title' => $this->faker->sentence,
            'description' => 12345,
            'status' => 'pending'
        ];

        $response = $this->postJson('/tasks', $taskData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['description']);
    }
}

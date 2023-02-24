<?php

namespace Tests\Feature\Http\Controllers\Organization;

use App\Enums\UserRoleEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Lang;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateTest extends TestCase
{

    use RefreshDatabase;

    public function test_empty_patch_data()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $organization = Organization::factory()->create();

        $data = [
            'name'        => '',
            'description' => ''
        ];

        $response = $this->patchJson(
            route('api.organizations.update', [$organization->id]),
            $data
        );
        $response
            ->assertStatus(422)
            ->assertJsonMissingValidationErrors([
                'name'        => Lang::get('validation.required', ['attribute' => 'name']),
                'description' => Lang::get('validation.required', ['attribute' => 'description']),
            ]);
    }

    public function test_unauthorized()
    {
        $organization = Organization::factory()->create();

        $response = $this->patchJson(
            route('api.organizations.update', [$organization->id])
        );
        $response->assertUnauthorized();
    }

    public function test_not_found(){
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $data = [
            'name'        => '',
            'description' => ''
        ];

        $response = $this->patchJson(
            route('api.organizations.update', [46]),
            $data
        );
        $response->assertNotFound();
    }

    public function test_owner_can_update_organization()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $organization = Organization::factory()->create();

        $data = [
            'name'        => 'Test',
            'description' => 'Test description'
        ];

        $response = $this->patchJson(
            route('api.organizations.update', [$organization->id]),
            $data
        );
        $response
            ->assertOk()
            ->assertJson(
                fn(AssertableJson $json) => $json->has(
                    'data',
                    fn(AssertableJson $json) => $json->where('type', 'organization')
                        ->whereType('id', 'integer')
                        ->has('attributes', fn(AssertableJson $json) => $json
                            ->where('name', $data['name'])
                            ->where('description', $data['description'])
                        )
                )
            );
    }

    public function test_admin_can_update_organization()
    {
        $user = User::factory()->create(['role' => UserRoleEnum::ADMIN]);
        Sanctum::actingAs($user);

        $organization = Organization::factory()->create();

        $data = [
            'name'        => 'Test',
            'description' => 'Test description'
        ];

        $response = $this->patchJson(
            route('api.organizations.update', [$organization->id]),
            $data
        );
        $response
            ->assertOk()
            ->assertJson(
                fn(AssertableJson $json) => $json->has(
                    'data',
                    fn(AssertableJson $json) => $json->where('type', 'organization')
                        ->whereType('id', 'integer')
                        ->has('attributes', fn(AssertableJson $json) => $json
                            ->where('name', $data['name'])
                            ->where('description', $data['description'])
                        )
                )
            );
    }

    public function test_common_user_cannot_update_organization()
    {
        $user = User::factory()->create(['role' => UserRoleEnum::COMMON]);
        Sanctum::actingAs($user);

        $organization = Organization::factory()->create();

        $data = [
            'name'        => 'Test',
            'description' => 'Test description'
        ];

        $response = $this->patchJson(
            route('api.organizations.update', [$organization->id]),
            $data
        );
        $response->assertStatus(403);
    }
}

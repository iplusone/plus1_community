<?php

namespace Tests\Feature\Admin;

use App\Models\Company;
use App\Models\Spot;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpotHierarchyManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param array<string, mixed> $attributes
     */
    private function createSpot(array $attributes): Spot
    {
        return Spot::query()->create($attributes + [
            'is_public' => false,
            'view_count' => 0,
            'sort_order' => 0,
        ]);
    }

    public function test_parent_options_only_show_same_company_manageable_spots(): void
    {
        $company = Company::query()->create(['name' => 'Alpha']);
        $otherCompany = Company::query()->create(['name' => 'Beta']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $root = $this->createSpot([
            'company_id' => $company->id,
            'name' => '本部',
            'slug' => 'hq',
            'depth' => 1,
        ]);
        $child = $this->createSpot([
            'company_id' => $company->id,
            'parent_id' => $root->id,
            'name' => '関東支社',
            'slug' => 'kanto',
            'depth' => 2,
        ]);
        $other = $this->createSpot([
            'company_id' => $otherCompany->id,
            'name' => '別会社本部',
            'slug' => 'other-hq',
            'depth' => 1,
        ]);

        $user->adminSpots()->attach($root->id, ['role_scope' => 'self_and_descendants']);

        $response = $this->actingAs($user)->get(route('admin.spots.edit', $child));

        $response->assertOk();
        $response->assertSee('本部（第1階層）', false);
        $response->assertDontSee('別会社本部', false);
    }

    public function test_cannot_assign_descendant_as_parent(): void
    {
        $company = Company::query()->create(['name' => 'Alpha']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $root = $this->createSpot([
            'company_id' => $company->id,
            'name' => '本部',
            'slug' => 'hq',
            'depth' => 1,
        ]);
        $child = $this->createSpot([
            'company_id' => $company->id,
            'parent_id' => $root->id,
            'name' => '支社',
            'slug' => 'branch',
            'depth' => 2,
        ]);

        $user->adminSpots()->attach($root->id, ['role_scope' => 'self_and_descendants']);

        $response = $this->actingAs($user)
            ->from(route('admin.spots.edit', $root))
            ->put(route('admin.spots.update', $root), [
                'parent_id' => $child->id,
                'name' => $root->name,
                'slug' => $root->slug,
            ]);

        $response->assertRedirect(route('admin.spots.edit', $root));
        $response->assertSessionHasErrors([
            'parent_id' => '配下のスポットは親スポットに設定できません。',
        ]);
    }

    public function test_cannot_assign_parent_beyond_five_levels(): void
    {
        $company = Company::query()->create(['name' => 'Alpha']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $level1 = $this->createSpot(['company_id' => $company->id, 'name' => 'L1', 'slug' => 'l1', 'depth' => 1]);
        $level2 = $this->createSpot(['company_id' => $company->id, 'parent_id' => $level1->id, 'name' => 'L2', 'slug' => 'l2', 'depth' => 2]);
        $level3 = $this->createSpot(['company_id' => $company->id, 'parent_id' => $level2->id, 'name' => 'L3', 'slug' => 'l3', 'depth' => 3]);
        $level4 = $this->createSpot(['company_id' => $company->id, 'parent_id' => $level3->id, 'name' => 'L4', 'slug' => 'l4', 'depth' => 4]);
        $level5 = $this->createSpot(['company_id' => $company->id, 'parent_id' => $level4->id, 'name' => 'L5', 'slug' => 'l5', 'depth' => 5]);

        $user->adminSpots()->attach($level1->id, ['role_scope' => 'self_and_descendants']);

        $response = $this->actingAs($user)
            ->from(route('admin.spots.create'))
            ->post(route('admin.spots.store'), [
                'parent_id' => $level5->id,
                'name' => 'L6',
                'slug' => 'l6',
            ]);

        $response->assertRedirect(route('admin.spots.create'));
        $response->assertSessionHasErrors([
            'parent_id' => '親スポットを設定すると最大5階層を超えるため保存できません。',
        ]);
    }
}

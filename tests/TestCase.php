<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    protected Team $team;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->team = Team::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->teams()->attach($this->team);

        // Team-scoped Spatie Permission setup — assign all roles so the user
        // can access all 3 panels (admin / store / support).
        app(PermissionRegistrar::class)->setPermissionsTeamId($this->team->id);
        foreach (['admin', 'manager', 'support'] as $roleName) {
            $role = Role::firstOrCreate([
                'name' => $roleName,
                'team_id' => $this->team->id,
                'guard_name' => 'web',
            ]);
            $this->admin->assignRole($role);
        }

        $this->actingAs($this->admin->fresh());

        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->setTenant($this->team);
    }

    protected function tearDown(): void
    {
        Livewire::flushState();

        // Per-test state resets — without these, intra-worker flakes increase.
        // See README for the full set of mitigations applied to a real project.
        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);
        }
        app()->forgetInstance(PermissionRegistrar::class);
        app()->forgetInstance('filament');
        if (app()->bound(\Livewire\Mechanisms\ComponentRegistry::class)) {
            app()->forgetInstance(\Livewire\Mechanisms\ComponentRegistry::class);
        }

        parent::tearDown();
    }
}

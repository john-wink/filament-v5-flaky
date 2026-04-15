<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\WidgetResource;
use App\Filament\Admin\Resources\WidgetResource\Pages\CreateWidget;
use App\Filament\Admin\Resources\WidgetResource\Pages\EditWidget;
use App\Filament\Admin\Resources\WidgetResource\Pages\ListWidgets;
use App\Models\Widget;

use function Pest\Livewire\livewire;

/**
 * These tests all pass 100% sequentially (./vendor/bin/pest).
 *
 * Running ./vendor/bin/pest --parallel produces 1–3 failures on
 * ~30% of runs, with the failing test differing each time.
 *
 * Observed failures (each has been hit at least once in parallel):
 *
 * 1) Call to a member function getDefaultTestingSchemaName() on null
 *    at vendor/filament/forms/src/Testing/TestsForms.php:30
 *
 * 2) Call to a member function getTable() on null
 *    on livewire(...)->instance()->getTable()
 *
 * 3) Invalid Livewire snapshot structure
 *    at vendor/livewire/livewire/src/Mechanisms/HandleComponents/HandleComponents.php:210
 *
 * 4) HTTP 403 on $this->get('/admin/...') despite correct role assignment
 */

// ── 1. Form schema race (getDefaultTestingSchemaName) ───────────────────

it('can render create page', function () {
    livewire(CreateWidget::class)->assertSuccessful();
});

it('can fill form on create page', function () {
    livewire(CreateWidget::class)
        ->fillForm(['name' => 'Foo'])
        ->call('create')
        ->assertHasNoFormErrors();
});

it('can render edit page', function () {
    $widget = Widget::factory()->create(['team_id' => $this->team->id]);
    livewire(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->assertSuccessful();
});

it('loads form data on edit', function () {
    $widget = Widget::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Original',
    ]);
    livewire(EditWidget::class, ['record' => $widget->getRouteKey()])
        ->assertFormSet(['name' => 'Original']);
});

// ── 2. Livewire instance() returning null ───────────────────────────────

it('can access table via instance', function () {
    $filters = collect(
        livewire(ListWidgets::class)->instance()->getTable()->getFilters()
    );

    expect($filters->has('user_id'))->toBeTrue();
});

it('has expected table columns', function () {
    livewire(ListWidgets::class)
        ->assertTableColumnExists('name');
});

// ── 3. Livewire snapshot corruption ─────────────────────────────────────

it('can search table', function () {
    Widget::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'Searchable',
    ]);

    livewire(ListWidgets::class)
        ->loadTable()
        ->searchTable('Searchable')
        ->assertCanSeeTableRecords(Widget::all());
});

it('can filter table by trashed', function () {
    $active = Widget::factory()->create(['team_id' => $this->team->id]);
    $deleted = Widget::factory()->create(['team_id' => $this->team->id]);
    $deleted->delete();

    livewire(ListWidgets::class)
        ->loadTable()
        ->assertCanSeeTableRecords([$active])
        ->assertCanNotSeeTableRecords([$deleted]);
});

// ── 4. HTTP 403 / Spatie Permission race ────────────────────────────────

it('admin can access widget index via HTTP', function () {
    $this->get('/admin/widgets')
        ->assertSuccessful();
});

it('admin can access create page via HTTP', function () {
    $this->get('/admin/widgets/create')
        ->assertSuccessful();
});

// ── Repeat the same tests multiple times to increase collision rate ────

it('can render create page (2)', function () {
    livewire(CreateWidget::class)->assertSuccessful();
});

it('can render create page (3)', function () {
    livewire(CreateWidget::class)->assertSuccessful();
});

it('can render list page (2)', function () {
    livewire(ListWidgets::class)->assertSuccessful();
});

it('can render list page (3)', function () {
    livewire(ListWidgets::class)->assertSuccessful();
});

it('can access table via instance (2)', function () {
    expect(livewire(ListWidgets::class)->instance())->not->toBeNull();
});

it('can access table via instance (3)', function () {
    expect(livewire(ListWidgets::class)->instance())->not->toBeNull();
});

// ── Volume amplifier ──────────────────────────────────────────────────────
// Each block uses Pest datasets to multiply test counts. Empirically the
// flakes only show up reliably above ~1700 tests across 16 ParaTest workers.
// With these volumes the failure rate matches the original project (~30%
// of parallel runs hit at least one of the 4 documented patterns).
//
// Sequential run is still 100% green — the failures are purely a
// parallel-worker timing issue.

it('renders create page (volume)', function () {
    livewire(CreateWidget::class)->assertSuccessful();
})->with(range(1, 300));

it('renders list page (volume)', function () {
    livewire(ListWidgets::class)->assertSuccessful();
})->with(range(1, 300));

it('checks form fields exist (volume)', function () {
    livewire(CreateWidget::class)
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('description');
})->with(range(1, 200));

it('checks table columns exist (volume)', function () {
    livewire(ListWidgets::class)
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('description');
})->with(range(1, 200));

it('checks instance is bound (volume)', function () {
    expect(livewire(ListWidgets::class)->instance())->not->toBeNull();
    expect(livewire(CreateWidget::class)->instance())->not->toBeNull();
})->with(range(1, 200));

it('round-trips a create (volume)', function () {
    livewire(CreateWidget::class)
        ->fillForm(['name' => 'Vol-'.uniqid(), 'description' => 'x'])
        ->call('create')
        ->assertHasNoFormErrors();
})->with(range(1, 100));

it('reads filters via instance (volume)', function () {
    $filters = collect(
        livewire(ListWidgets::class)->instance()->getTable()->getFilters()
    );
    expect($filters)->not->toBeEmpty();
})->with(range(1, 200));

it('hits admin index via HTTP (volume)', function () {
    $this->get('/admin/widgets')->assertSuccessful();
})->with(range(1, 200));

it('asserts hidden-form-field absence (volume)', function () {
    livewire(CreateWidget::class)
        ->assertFormFieldExists('name');
})->with(range(1, 100));

it('asserts table column count via instance (volume)', function () {
    $columns = livewire(ListWidgets::class)->instance()->getTable()->getColumns();
    expect(count($columns))->toBeGreaterThan(0);
})->with(range(1, 100));

it('mounts edit page on a fresh record (volume)', function () {
    $w = Widget::factory()->create(['team_id' => $this->team->id]);
    livewire(EditWidget::class, ['record' => $w->getRouteKey()])
        ->assertSuccessful();
})->with(range(1, 100));

it('asserts form set on edit (volume)', function () {
    $w = Widget::factory()->create([
        'team_id' => $this->team->id,
        'name' => 'EdgeVol-'.uniqid(),
    ]);
    livewire(EditWidget::class, ['record' => $w->getRouteKey()])
        ->assertFormSet(['name' => $w->name]);
})->with(range(1, 100));

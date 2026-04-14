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
    $this->get('/admin/'.$this->team->id.'/widgets')
        ->assertSuccessful();
});

it('admin can access create page via HTTP', function () {
    $this->get('/admin/'.$this->team->id.'/widgets/create')
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

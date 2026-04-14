<?php

declare(strict_types=1);

// These tests deliberately switch panels mid-test to maximise the chance
// of a Filament-Manager static-state race when two ParaTest workers happen
// to be in different panel contexts simultaneously.

use App\Filament\Admin\Resources\TagResource\Pages\ListTags;
use App\Filament\Admin\Resources\WidgetResource\Pages\ListWidgets;
use App\Filament\Store\Resources\CouponResource\Pages\ListCoupons;
use App\Filament\Store\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Support\Resources\TicketResource\Pages\ListTickets;

use function Pest\Livewire\livewire;

it('Cross-panel: switch admin → store mid-test (volume)', function () {
    livewire(ListWidgets::class)->assertSuccessful();

    filament()->setCurrentPanel(filament()->getPanel('store'));
    filament()->setTenant(null);

    livewire(ListProducts::class)->assertSuccessful();
})->with(range(1, 100));

it('Cross-panel: switch admin → support mid-test (volume)', function () {
    livewire(ListTags::class)->assertSuccessful();

    filament()->setCurrentPanel(filament()->getPanel('support'));
    filament()->setTenant($this->team);

    livewire(ListTickets::class)->assertSuccessful();
})->with(range(1, 100));

it('Cross-panel: 3-way panel rotation per test (volume)', function () {
    // admin
    filament()->setCurrentPanel(filament()->getPanel('admin'));
    filament()->setTenant($this->team);
    expect(livewire(ListWidgets::class)->instance())->not->toBeNull();

    // store
    filament()->setCurrentPanel(filament()->getPanel('store'));
    filament()->setTenant(null);
    expect(livewire(ListCoupons::class)->instance())->not->toBeNull();

    // support
    filament()->setCurrentPanel(filament()->getPanel('support'));
    filament()->setTenant($this->team);
    expect(livewire(ListTickets::class)->instance())->not->toBeNull();
})->with(range(1, 100));

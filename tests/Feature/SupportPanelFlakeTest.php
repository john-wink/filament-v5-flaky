<?php

declare(strict_types=1);

use App\Filament\Support\Resources\TicketResource;
use App\Filament\Support\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Support\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Support\Resources\TicketResource\Pages\ListTickets;
use App\Models\Ticket;

use function Pest\Livewire\livewire;

beforeEach(function () {
    filament()->setCurrentPanel(filament()->getPanel('support'));
    filament()->setTenant($this->team);
});

it('Support: list tickets (volume)', function () {
    livewire(ListTickets::class)->assertSuccessful();
})->with(range(1, 200));

it('Support: create ticket page (volume)', function () {
    livewire(CreateTicket::class)->assertSuccessful();
})->with(range(1, 200));

it('Support: ticket form fields (volume)', function () {
    livewire(CreateTicket::class)
        ->assertFormFieldExists('subject')
        ->assertFormFieldExists('body')
        ->assertFormFieldExists('status');
})->with(range(1, 150));

it('Support: ticket table columns (volume)', function () {
    livewire(ListTickets::class)
        ->assertTableColumnExists('subject')
        ->assertTableColumnExists('status');
})->with(range(1, 150));

it('Support: ticket instance (volume)', function () {
    expect(livewire(ListTickets::class)->instance())->not->toBeNull();
})->with(range(1, 150));

it('Support: ticket filter via instance (volume)', function () {
    $filters = collect(
        livewire(ListTickets::class)->instance()->getTable()->getFilters()
    );
    expect($filters->has('status'))->toBeTrue();
})->with(range(1, 100));

it('Support: edit ticket (volume)', function () {
    $t = Ticket::factory()->create([
        'team_id' => $this->team->id,
        'user_id' => $this->admin->id,
    ]);
    livewire(EditTicket::class, ['record' => $t->getRouteKey()])->assertSuccessful();
})->with(range(1, 100));

it('Support: HTTP ticket index (volume)', function () {
    $this->get('/support/'.$this->team->id.'/tickets')->assertSuccessful();
})->with(range(1, 100));

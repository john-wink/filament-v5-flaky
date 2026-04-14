<?php

declare(strict_types=1);

use App\Filament\Admin\Resources\TagResource;
use App\Filament\Admin\Resources\TagResource\Pages\CreateTag;
use App\Filament\Admin\Resources\TagResource\Pages\EditTag;
use App\Filament\Admin\Resources\TagResource\Pages\ListTags;
use App\Models\Tag;

use function Pest\Livewire\livewire;

it('Admin: list tags (volume)', function () {
    livewire(ListTags::class)->assertSuccessful();
})->with(range(1, 200));

it('Admin: create tag page (volume)', function () {
    livewire(CreateTag::class)->assertSuccessful();
})->with(range(1, 200));

it('Admin: tag form fields (volume)', function () {
    livewire(CreateTag::class)
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('color');
})->with(range(1, 150));

it('Admin: tag instance (volume)', function () {
    expect(livewire(ListTags::class)->instance())->not->toBeNull();
})->with(range(1, 150));

it('Admin: edit tag (volume)', function () {
    $tag = Tag::factory()->create();
    livewire(EditTag::class, ['record' => $tag->getRouteKey()])->assertSuccessful();
})->with(range(1, 100));

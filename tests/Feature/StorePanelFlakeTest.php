<?php

declare(strict_types=1);

use App\Filament\Store\Resources\CouponResource;
use App\Filament\Store\Resources\CouponResource\Pages\CreateCoupon;
use App\Filament\Store\Resources\CouponResource\Pages\ListCoupons;
use App\Filament\Store\Resources\ProductResource;
use App\Filament\Store\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Store\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Store\Resources\ProductResource\Pages\ListProducts;
use App\Models\Coupon;
use App\Models\Product;

use function Pest\Livewire\livewire;

beforeEach(function () {
    filament()->setCurrentPanel(filament()->getPanel('store'));
    filament()->setTenant(null);   // store is not tenant-scoped
});

// ── Store: Product render volume ─────────────────────────────────────────

it('Store: list products (volume)', function () {
    livewire(ListProducts::class)->assertSuccessful();
})->with(range(1, 200));

it('Store: create product page (volume)', function () {
    livewire(CreateProduct::class)->assertSuccessful();
})->with(range(1, 200));

it('Store: product form fields (volume)', function () {
    livewire(CreateProduct::class)
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('sku')
        ->assertFormFieldExists('price');
})->with(range(1, 150));

it('Store: product table columns (volume)', function () {
    livewire(ListProducts::class)
        ->assertTableColumnExists('name')
        ->assertTableColumnExists('sku');
})->with(range(1, 150));

it('Store: product instance via livewire (volume)', function () {
    expect(livewire(ListProducts::class)->instance())->not->toBeNull();
})->with(range(1, 150));

it('Store: product edit page (volume)', function () {
    $p = Product::factory()->create();
    livewire(EditProduct::class, ['record' => $p->getRouteKey()])->assertSuccessful();
})->with(range(1, 100));

// ── Store: Coupon (different resource within same panel, same worker) ───

it('Store: list coupons (volume)', function () {
    livewire(ListCoupons::class)->assertSuccessful();
})->with(range(1, 200));

it('Store: create coupon (volume)', function () {
    livewire(CreateCoupon::class)->assertSuccessful();
})->with(range(1, 150));

it('Store: coupon table instance (volume)', function () {
    expect(livewire(ListCoupons::class)->instance())->not->toBeNull();
})->with(range(1, 150));

it('Store: HTTP product index (volume)', function () {
    $this->get('/store/products')->assertSuccessful();
})->with(range(1, 100));

it('Store: HTTP coupon index (volume)', function () {
    $this->get('/store/coupons')->assertSuccessful();
})->with(range(1, 100));

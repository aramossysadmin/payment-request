<?php

use App\Filament\Resources\CurrencyResource\Pages\CreateCurrency;
use App\Filament\Resources\CurrencyResource\Pages\EditCurrency;
use App\Filament\Resources\CurrencyResource\Pages\ListCurrencies;
use App\Models\Currency;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();
    $this->superAdmin = User::factory()->create();
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->superAdmin->assignRole($role);
    $this->actingAs($this->superAdmin);
});

it('can render the list page', function () {
    Livewire::test(ListCurrencies::class)
        ->assertSuccessful();
});

it('can list currencies', function () {
    $currencies = Currency::factory()->count(3)->create();

    Livewire::test(ListCurrencies::class)
        ->assertCanSeeTableRecords($currencies);
});

it('can render the create page', function () {
    Livewire::test(CreateCurrency::class)
        ->assertSuccessful();
});

it('can create a currency', function () {
    Livewire::test(CreateCurrency::class)
        ->set('data.name', 'Peso Mexicano')
        ->set('data.prefix', 'MXN')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('currencies', [
        'name' => 'PESO MEXICANO',
        'prefix' => 'MXN',
    ]);
});

it('validates required fields on create', function () {
    Livewire::test(CreateCurrency::class)
        ->fillForm([
            'name' => '',
            'prefix' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'prefix' => 'required',
        ]);
});

it('validates unique name on create', function () {
    Currency::factory()->create(['name' => 'DÓLAR']);

    Livewire::test(CreateCurrency::class)
        ->set('data.name', 'DÓLAR')
        ->set('data.prefix', 'USD')
        ->call('create')
        ->assertHasFormErrors(['name']);
});

it('can render the edit page', function () {
    $currency = Currency::factory()->create();

    Livewire::test(EditCurrency::class, ['record' => $currency->getRouteKey()])
        ->assertSuccessful();
});

it('can edit a currency', function () {
    $currency = Currency::factory()->create();

    Livewire::test(EditCurrency::class, ['record' => $currency->getRouteKey()])
        ->set('data.name', 'Euro')
        ->set('data.prefix', 'EUR')
        ->call('save')
        ->assertHasNoFormErrors();

    $currency->refresh();
    expect($currency->name)->toBe('EURO');
    expect($currency->prefix)->toBe('EUR');
});

it('can soft delete a currency', function () {
    $currency = Currency::factory()->create();

    Livewire::test(ListCurrencies::class)
        ->callTableAction('delete', $currency);

    $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
});

it('can restore a soft deleted currency', function () {
    $currency = Currency::factory()->create();
    $currency->delete();

    Livewire::test(EditCurrency::class, ['record' => $currency->getRouteKey()])
        ->callAction('restore');

    $currency->refresh();
    expect($currency->deleted_at)->toBeNull();
});

it('can search currencies by name', function () {
    $currency = Currency::factory()->create(['name' => 'PESO MEXICANO']);
    $other = Currency::factory()->create(['name' => 'EURO']);

    Livewire::test(ListCurrencies::class)
        ->searchTable('PESO MEXICANO')
        ->assertCanSeeTableRecords([$currency])
        ->assertCanNotSeeTableRecords([$other]);
});

it('can search currencies by prefix', function () {
    $currency = Currency::factory()->create(['prefix' => 'MXN']);
    $other = Currency::factory()->create(['prefix' => 'EUR']);

    Livewire::test(ListCurrencies::class)
        ->searchTable('MXN')
        ->assertCanSeeTableRecords([$currency])
        ->assertCanNotSeeTableRecords([$other]);
});

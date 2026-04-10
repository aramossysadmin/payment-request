<?php

use App\Models\InvestmentRequest;
use App\Models\InvestmentRequestApproval;
use App\Models\User;
use App\States\InvestmentRequest\PendingDepartment;

it('can create an investment request using factory', function () {
    $investmentRequest = InvestmentRequest::factory()->create();

    expect($investmentRequest)->toBeInstanceOf(InvestmentRequest::class)
        ->and($investmentRequest->uuid)->not->toBeEmpty()
        ->and($investmentRequest->folio_number)->toBeGreaterThan(0)
        ->and($investmentRequest->status)->toBeInstanceOf(PendingDepartment::class);
});

it('has correct relationships', function () {
    $investmentRequest = InvestmentRequest::factory()->create();

    expect($investmentRequest->user)->toBeInstanceOf(User::class)
        ->and($investmentRequest->department)->not->toBeNull()
        ->and($investmentRequest->currency)->not->toBeNull()
        ->and($investmentRequest->branch)->not->toBeNull()
        ->and($investmentRequest->expenseConcept)->not->toBeNull();
});

it('auto generates uuid and folio on creation', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'uuid' => null,
        'folio_number' => null,
    ]);

    expect($investmentRequest->uuid)->not->toBeEmpty()
        ->and($investmentRequest->folio_number)->toBe(1);
});

it('generates independent folio sequence', function () {
    InvestmentRequest::factory()->create(['folio_number' => null]);
    $second = InvestmentRequest::factory()->create(['folio_number' => null]);

    expect($second->folio_number)->toBe(2);
});

it('uppercases provider and rfc', function () {
    $investmentRequest = InvestmentRequest::factory()->create([
        'provider' => 'acme corp',
        'rfc' => 'abc123456789',
    ]);

    expect($investmentRequest->provider)->toBe('ACME CORP')
        ->and($investmentRequest->rfc)->toBe('ABC123456789');
});

it('can have approvals', function () {
    $investmentRequest = InvestmentRequest::factory()->create();
    $approval = InvestmentRequestApproval::factory()->create([
        'investment_request_id' => $investmentRequest->id,
    ]);

    expect($investmentRequest->approvals)->toHaveCount(1)
        ->and($approval->investmentRequest->id)->toBe($investmentRequest->id);
});

it('creates approval with token', function () {
    $approval = InvestmentRequestApproval::factory()->withToken()->create();

    expect($approval->hasValidToken())->toBeTrue()
        ->and($approval->isPending())->toBeTrue();
});

it('detects expired token', function () {
    $approval = InvestmentRequestApproval::factory()->withExpiredToken()->create();

    expect($approval->isTokenExpired())->toBeTrue()
        ->and($approval->hasValidToken())->toBeFalse();
});

it('uses route key name uuid', function () {
    $investmentRequest = InvestmentRequest::factory()->create();

    expect($investmentRequest->getRouteKeyName())->toBe('uuid');
});

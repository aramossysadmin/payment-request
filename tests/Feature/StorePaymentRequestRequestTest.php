<?php

use App\Models\Branch;
use App\Models\Currency;
use App\Models\Department;
use App\Models\ExpenseConcept;
use App\Models\PaymentType;
use App\Models\User;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->department = Department::factory()->create();
    $this->user = User::factory()->create(['department_id' => $this->department->id]);
    $this->department->authorizers()->attach($this->user->id);

    $this->advanceType = PaymentType::factory()->create(['requires_invoice_documents' => false]);
    $this->invoiceType = PaymentType::factory()->create(['requires_invoice_documents' => true]);

    $this->validData = [
        'provider' => 'Test Provider',
        'invoice_folio' => 'FAC-0001',
        'currency_id' => Currency::factory()->create()->id,
        'branch_id' => Branch::factory()->create()->id,
        'expense_concept_id' => ExpenseConcept::factory()->create()->id,
        'payment_type_id' => $this->advanceType->id,
        'subtotal' => 1000.00,
        'iva_rate' => '0.16',
        'iva' => 160.00,
        'retention' => false,
        'total' => 1160.00,
    ];
});

test('validation passes with valid data', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), $this->validData)
        ->assertRedirect(route('payment-requests.index'));
});

test('provider is required', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'provider' => ''])
        ->assertSessionHasErrors('provider');
});

test('provider must be a string with max 255 characters', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'provider' => str_repeat('a', 256)])
        ->assertSessionHasErrors('provider');
});

test('invoice_folio is required', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'invoice_folio' => ''])
        ->assertSessionHasErrors('invoice_folio');
});

test('currency_id must exist in currencies table', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'currency_id' => 99999])
        ->assertSessionHasErrors('currency_id');
});

test('branch_id must exist in branches table', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'branch_id' => 99999])
        ->assertSessionHasErrors('branch_id');
});

test('expense_concept_id must exist in expense_concepts table', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'expense_concept_id' => 99999])
        ->assertSessionHasErrors('expense_concept_id');
});

test('payment_type_id must exist in payment_types table', function (mixed $invalidId) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'payment_type_id' => $invalidId])
        ->assertSessionHasErrors('payment_type_id');
})->with([
    99999,
    'invalid',
    '',
]);

test('invoice payment type requires pdf and xml documents', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'payment_type_id' => $this->invoiceType->id])
        ->assertSessionHasErrors('advance_documents');
});

test('invoice payment type is accepted with pdf and xml documents', function () {
    $pdf = UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf');
    $xml = UploadedFile::fake()->create('factura.xml', 50, 'text/xml');

    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [
            ...$this->validData,
            'payment_type_id' => $this->invoiceType->id,
            'advance_documents' => [$pdf, $xml],
        ])
        ->assertRedirect(route('payment-requests.index'));
});

test('invoice payment type rejects two pdfs', function () {
    $pdf1 = UploadedFile::fake()->create('factura1.pdf', 100, 'application/pdf');
    $pdf2 = UploadedFile::fake()->create('factura2.pdf', 100, 'application/pdf');

    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [
            ...$this->validData,
            'payment_type_id' => $this->invoiceType->id,
            'advance_documents' => [$pdf1, $pdf2],
        ])
        ->assertSessionHasErrors('advance_documents');
});

test('invoice payment type rejects more than two files', function () {
    $pdf = UploadedFile::fake()->create('factura.pdf', 100, 'application/pdf');
    $xml = UploadedFile::fake()->create('factura.xml', 50, 'text/xml');
    $extra = UploadedFile::fake()->create('extra.pdf', 100, 'application/pdf');

    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [
            ...$this->validData,
            'payment_type_id' => $this->invoiceType->id,
            'advance_documents' => [$pdf, $xml, $extra],
        ])
        ->assertSessionHasErrors('advance_documents');
});

test('non-invoice payment types allow up to 10 documents', function () {
    $files = [];
    for ($i = 0; $i < 10; $i++) {
        $files[] = UploadedFile::fake()->create("doc{$i}.pdf", 100, 'application/pdf');
    }

    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [
            ...$this->validData,
            'payment_type_id' => $this->advanceType->id,
            'advance_documents' => $files,
        ])
        ->assertRedirect(route('payment-requests.index'));
});

test('non-invoice payment types reject more than 10 documents', function () {
    $files = [];
    for ($i = 0; $i < 11; $i++) {
        $files[] = UploadedFile::fake()->create("doc{$i}.pdf", 100, 'application/pdf');
    }

    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [
            ...$this->validData,
            'payment_type_id' => $this->advanceType->id,
            'advance_documents' => $files,
        ])
        ->assertSessionHasErrors('advance_documents');
});

test('subtotal must be numeric and non-negative', function (mixed $value) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'subtotal' => $value])
        ->assertSessionHasErrors('subtotal');
})->with([
    'abc',
    -1,
]);

test('total must be numeric and non-negative', function (mixed $value) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'total' => $value])
        ->assertSessionHasErrors('total');
})->with([
    'abc',
    -1,
]);

test('rfc is optional', function () {
    $data = $this->validData;
    unset($data['rfc']);

    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), $data)
        ->assertRedirect(route('payment-requests.index'));
});

test('rfc must be alphanumeric', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'rfc' => 'ABC-123456-X'])
        ->assertSessionHasErrors('rfc');
});

test('rfc must have at least 12 characters', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'rfc' => 'ABC1234567'])
        ->assertSessionHasErrors('rfc');
});

test('rfc must not exceed 13 characters', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'rfc' => 'ABCD123456789X'])
        ->assertSessionHasErrors('rfc');
});

test('valid rfc with 12 characters is accepted', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'rfc' => 'XAXX010101000'])
        ->assertRedirect(route('payment-requests.index'));
});

test('valid rfc with 13 characters is accepted', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'rfc' => 'GARC850101AB1'])
        ->assertRedirect(route('payment-requests.index'));
});

test('description is optional', function () {
    $data = $this->validData;
    unset($data['description']);

    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), $data)
        ->assertRedirect(route('payment-requests.index'));
});

test('iva_rate is required', function () {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'iva_rate' => ''])
        ->assertSessionHasErrors('iva_rate');
});

test('valid iva_rate values are accepted', function (string $rate) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'iva_rate' => $rate])
        ->assertRedirect(route('payment-requests.index'));
})->with([
    '0.00',
    '0.08',
    '0.16',
]);

test('invalid iva_rate values are rejected', function (mixed $rate) {
    $this->actingAs($this->user)
        ->post(route('payment-requests.store'), [...$this->validData, 'iva_rate' => $rate])
        ->assertSessionHasErrors('iva_rate');
})->with([
    '0.05',
    '16',
    'abc',
]);

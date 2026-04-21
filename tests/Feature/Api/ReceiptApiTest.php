<?php

use App\Models\Obligation;
use App\Models\Receipt;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->obligation = Obligation::create([
        'title' => 'Monthly Payment - Test',
        'amount_expected' => 1000.00,
        'due_date' => '2026-04-15',
        'frequency' => 'monthly',
        'status' => 'pending',
    ]);

    $this->receipt = Receipt::create([
        'obligation_id' => $this->obligation->id,
        'amount_received' => 500.00,
        'date_received' => '2026-04-14',
        'payment_method' => 'cash',
    ]);
});

describe('Receipt API', function () {
    it('can create a receipt', function () {
        $response = $this->postJson('/api/v1/receipts', [
            'obligation_id' => $this->obligation->id,
            'amount_received' => 300.00,
            'date_received' => '2026-04-14',
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('receipts', [
            'amount_received' => 300.00,
        ]);
    });

    it('can list all receipts', function () {
        $response = $this->getJson('/api/v1/receipts');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can list receipts by obligation', function () {
        $response = $this->getJson("/api/v1/receipts?obligation_id={$this->obligation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can show a single receipt', function () {
        $response = $this->getJson("/api/v1/receipts/{$this->receipt->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can delete a receipt', function () {
        $response = $this->deleteJson("/api/v1/receipts/{$this->receipt->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('receipts', [
            'id' => $this->receipt->id,
        ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/receipts', [
            'amount_received' => 100,
        ]);

        $response->assertStatus(422);
    });

    it('validates obligation exists', function () {
        $response = $this->postJson('/api/v1/receipts', [
            'obligation_id' => 99999,
            'amount_received' => 100,
            'date_received' => '2026-04-14',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422);
    });

    it('calculates total remitted from receipt', function () {
        expect($this->receipt->total_remitted)->toBe(0.0);
    });

    it('calculates balance from receipt', function () {
        expect($this->receipt->balance)->toBe(500.00);
    });
});

<?php

use App\Models\Obligation;
use App\Models\Receipt;
use App\Models\Remittance;
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
        'amount_received' => 1000.00,
        'date_received' => '2026-04-14',
        'payment_method' => 'cash',
    ]);

    $this->remittance = Remittance::create([
        'receipt_id' => $this->receipt->id,
        'amount_paid' => 500.00,
        'date_paid' => '2026-04-15',
        'payment_method' => 'bank_transfer',
    ]);
});

describe('Remittance API', function () {
    it('can create a remittance', function () {
        $response = $this->postJson('/api/v1/remittances', [
            'receipt_id' => $this->receipt->id,
            'amount_paid' => 300.00,
            'date_paid' => '2026-04-16',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('remittances', [
            'amount_paid' => 300.00,
        ]);
    });

    it('can list all remittances', function () {
        $response = $this->getJson('/api/v1/remittances');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can list remittances by date range', function () {
        $response = $this->getJson('/api/v1/remittances?from=2026-04-01&to=2026-04-30');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can show a single remittance', function () {
        $response = $this->getJson("/api/v1/remittances/{$this->remittance->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can delete a remittance', function () {
        $response = $this->deleteJson("/api/v1/remittances/{$this->remittance->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('remittances', [
            'id' => $this->remittance->id,
        ]);
    });

    it('validates required fields', function () {
        $response = $this->postJson('/api/v1/remittances', [
            'amount_paid' => 100,
        ]);

        $response->assertStatus(422);
    });

    it('validates receipt exists', function () {
        $response = $this->postJson('/api/v1/remittances', [
            'receipt_id' => 99999,
            'amount_paid' => 100,
            'date_paid' => '2026-04-14',
            'payment_method' => 'cash',
        ]);

        $response->assertStatus(422);
    });
});

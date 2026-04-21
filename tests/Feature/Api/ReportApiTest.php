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
        'amount_paid' => 1000.00,
        'date_paid' => '2026-04-15',
        'payment_method' => 'bank_transfer',
    ]);
});

describe('Report API', function () {
    it('can get monthly summary report', function () {
        $response = $this->getJson('/api/v1/reports/monthly?month=04&year=2026');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can get full statement report', function () {
        $response = $this->getJson('/api/v1/reports/statement?from=2026-01-01&to=2026-12-31');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can get outstanding payments report', function () {
        $response = $this->getJson('/api/v1/reports/outstanding');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can get overdue payments report', function () {
        $overdueObligation = Obligation::create([
            'title' => 'Overdue Payment',
            'amount_expected' => 500.00,
            'due_date' => '2020-01-01',
            'frequency' => 'monthly',
            'status' => 'overdue',
        ]);

        $response = $this->getJson('/api/v1/reports/overdue');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can get dashboard report', function () {
        $response = $this->getJson('/api/v1/reports/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'totals' => [
                        'expected',
                        'received',
                        'remitted',
                        'outstanding',
                    ],
                    'counts' => [
                        'pending',
                        'overdue',
                        'received',
                        'remitted',
                    ],
                ],
            ]);
    });

    it('returns correct structure for dashboard', function () {
        $response = $this->getJson('/api/v1/reports/dashboard');
        $data = $response->json('data');

        expect($data['totals']['expected'])->toBe(1000);
    });
});

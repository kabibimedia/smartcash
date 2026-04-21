<?php

use App\Models\Obligation;
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
});

describe('Obligation API', function () {
    it('can create an obligation', function () {
        $response = $this->postJson('/api/v1/obligations', [
            'title' => 'New Obligation',
            'amount_expected' => 500.00,
            'due_date' => '2026-05-15',
            'frequency' => 'monthly',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'New Obligation');

        $this->assertDatabaseHas('obligations', [
            'title' => 'New Obligation',
            'amount_expected' => 500.00,
        ]);
    });

    it('can list all obligations', function () {
        $response = $this->getJson('/api/v1/obligations');

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    });

    it('can show a single obligation', function () {
        $response = $this->getJson("/api/v1/obligations/{$this->obligation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', $this->obligation->title);
    });

    it('can update an obligation', function () {
        $response = $this->putJson("/api/v1/obligations/{$this->obligation->id}", [
            'title' => 'Updated Obligation',
            'amount_expected' => 1500.00,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Updated Obligation');

        $this->assertDatabaseHas('obligations', [
            'id' => $this->obligation->id,
            'title' => 'Updated Obligation',
        ]);
    });

    it('can delete an obligation', function () {
        $response = $this->deleteJson("/api/v1/obligations/{$this->obligation->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('obligations', [
            'id' => $this->obligation->id,
        ]);
    });

    it('validates required fields when creating obligation', function () {
        $response = $this->postJson('/api/v1/obligations', [
            'title' => '',
        ]);

        $response->assertStatus(422);
    });

    it('validates amount is numeric', function () {
        $response = $this->postJson('/api/v1/obligations', [
            'title' => 'Test',
            'amount_expected' => 'not-a-number',
            'due_date' => '2026-04-15',
            'frequency' => 'monthly',
        ]);

        $response->assertStatus(422);
    });

    it('calculates outstanding amount', function () {
        expect($this->obligation->outstanding)->toBe(1000.00);
    });

    it('checks if overdue', function () {
        $overdueObligation = Obligation::create([
            'title' => 'Overdue Test',
            'amount_expected' => 500.00,
            'due_date' => '2020-01-01',
            'frequency' => 'monthly',
            'status' => 'pending',
        ]);

        expect($overdueObligation->is_overdue)->toBeTrue();
    });
});

<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects forbidden blade directives', function () {
    $response = $this->postJson('/api/templates/validate', [
        'body' => 'Hello @php echo "x"; @endphp',
    ]);

    $response
        ->assertStatus(422)
        ->assertJsonStructure(['errors']);
});

it('renders a valid template', function () {
    $response = $this->postJson('/api/templates/validate', [
        'body' => 'Hello {{ $name }}',
        'sample_variables' => ['name' => 'Baran'],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonFragment(['rendered' => 'Hello Baran']);
});

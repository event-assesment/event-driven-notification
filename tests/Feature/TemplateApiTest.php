<?php

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists templates', function () {
    Template::factory()->count(3)->create();

    $response = $this->getJson('/api/templates');

    $response->assertSuccessful()->assertJsonCount(3, 'data');
});

it('creates a template', function () {
    $payload = [
        'name' => 'SMS Welcome',
        'channel' => 'sms',
        'body' => 'Hello {{ $name }}',
    ];

    $response = $this->postJson('/api/templates', $payload);

    $response
        ->assertCreated()
        ->assertJsonPath('data.name', 'SMS Welcome')
        ->assertJsonPath('data.channel', 'sms');

    $template = Template::query()->first();

    expect($template)->not()->toBeNull();
    expect($template->name)->toBe('SMS Welcome');
});

it('rejects unsafe templates on create', function () {
    $payload = [
        'name' => 'Unsafe Template',
        'channel' => 'email',
        'body' => 'Hello @php echo "x"; @endphp',
    ];

    $response = $this->postJson('/api/templates', $payload);

    $response->assertUnprocessable()->assertJsonValidationErrors(['body']);
});

it('shows a template', function () {
    $template = Template::factory()->create();

    $response = $this->getJson("/api/templates/{$template->id}");

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.id', $template->id);
});

it('updates a template', function () {
    $template = Template::factory()->create([
        'name' => 'Original',
        'body' => 'Hello {{ $name }}',
    ]);

    $payload = [
        'name' => 'Updated',
        'body' => 'Hi {{ $name }}',
    ];

    $response = $this->patchJson("/api/templates/{$template->id}", $payload);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.name', 'Updated');

    $template->refresh();

    expect($template->name)->toBe('Updated');
});

it('deletes a template', function () {
    $template = Template::factory()->create();

    $response = $this->deleteJson("/api/templates/{$template->id}");

    $response->assertNoContent();

    expect(Template::query()->count())->toBe(0);
});

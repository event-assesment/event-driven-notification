<?php

test('example', function () {
    $response = $this->get('/');

    $response->assertSuccessful()
        ->assertSee('Event-Driven Notification Console')
        ->assertSee('/api/documentation');
});

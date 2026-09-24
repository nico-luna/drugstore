<?php

test('the application renders the public home', function () {
    $response = $this->get('/');
    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Public/Home'));
});

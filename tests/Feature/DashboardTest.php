<?php

use App\Models\Users;

test('guests are redirected to the login page', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $this->actingAs($user = Users::factory()->create());

    $this->get(route('dashboard'))->assertOk();
});

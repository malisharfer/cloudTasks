<?php

it('redirects unauthorized users to the login page', function () {
    $this
        ->get('/')
        ->assertRedirect('/login');
});

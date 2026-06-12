<?php

test('the application redirects to the app panel', function () {
    $response = $this->get('/');

    $response->assertRedirect('/app');
});

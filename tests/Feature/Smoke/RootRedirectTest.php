<?php

namespace Tests\Feature\Smoke;

use App\Providers\RouteServiceProvider;
use Tests\TestCase;

class RootRedirectTest extends TestCase
{
    public function test_root_redirects_to_home(): void
    {
        $this->get('/')
            ->assertRedirect(RouteServiceProvider::HOME);
    }
}

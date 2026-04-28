<?php

namespace Tests\Feature;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_guests_are_redirected_to_the_login_page()
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        $user = new Authenticatable;
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }
}

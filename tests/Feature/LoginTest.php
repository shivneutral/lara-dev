<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_demo_user_can_login(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200)
            ->assertSee('Sign in');

        $response = $this->post('/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertTrue(session()->has('user'));
        $this->assertSame('Demo Admin', session('user.name'));
    }

    public function test_invalid_credentials_show_error(): void
    {
        $response = $this->post('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect();
        $this->assertNotEmpty($response->getSession()->get('errors'));
    }
}

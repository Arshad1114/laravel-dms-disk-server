<?php

namespace Arshad1114\DmsDiskServer\Tests\Unit;

use Arshad1114\DmsDiskServer\DmsServerServiceProvider;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class DmsTokenAuthTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [DmsServerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dms-disk-server.token', 'valid-test-token');
        Storage::fake();
    }

    public function test_valid_bearer_token_passes(): void
    {
        $response = $this->withToken('valid-test-token')
            ->getJson('/dms-disk/exists?path=test.txt');

        $this->assertNotEquals(401, $response->status());
    }

    public function test_invalid_token_returns_401(): void
    {
        $this->withToken('wrong-token')
            ->getJson('/dms-disk/exists?path=test.txt')
            ->assertStatus(401);
    }

    public function test_missing_token_returns_401(): void
    {
        $this->getJson('/dms-disk/exists?path=test.txt')
            ->assertStatus(401);
    }

    public function test_x_dms_token_header_is_accepted(): void
    {
        $response = $this->withHeaders(['X-DMS-Token' => 'valid-test-token'])
            ->getJson('/dms-disk/exists?path=test.txt');

        $this->assertNotEquals(401, $response->status());
    }

    public function test_empty_server_token_config_returns_500(): void
    {
        config(['dms-disk-server.token' => '']);

        $this->withToken('any-token')
            ->getJson('/dms-disk/exists?path=test.txt')
            ->assertStatus(500);
    }
}

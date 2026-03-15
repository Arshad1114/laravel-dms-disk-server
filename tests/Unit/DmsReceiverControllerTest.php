<?php

namespace Arshad1114\DmsDiskServer\Tests\Unit;

use Arshad1114\DmsDiskServer\DmsServerServiceProvider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;

class DmsReceiverControllerTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [DmsServerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('dms-disk-server.token', 'valid-test-token');
        $app['config']->set('dms-disk-server.max_file_size_kb', 10240);
        Storage::fake();
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer valid-test-token'];
    }

    // ── Upload ────────────────────────────────────────────────────────────────

    public function test_upload_stores_file(): void
    {
        $response = $this->withHeaders($this->auth())
            ->post('/dms-disk/upload', [
                'path' => 'invoices/001.pdf',
                'file' => UploadedFile::fake()->create('001.pdf', 100, 'application/pdf'),
            ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'ok', 'path' => 'invoices/001.pdf']);

        Storage::assertExists('invoices/001.pdf');
    }

    public function test_upload_requires_path_and_file(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/dms-disk/upload', [])
            ->assertStatus(422);
    }

    public function test_upload_sets_public_visibility(): void
    {
        $this->withHeaders($this->auth())
            ->post('/dms-disk/upload', [
                'path'       => 'public/banner.jpg',
                'file'       => UploadedFile::fake()->image('banner.jpg'),
                'visibility' => 'public',
            ])
            ->assertStatus(200);

        Storage::assertExists('public/banner.jpg');
    }

    // ── Download ──────────────────────────────────────────────────────────────

    public function test_download_returns_file_contents(): void
    {
        Storage::put('docs/file.txt', 'hello world');

        $this->withHeaders($this->auth())
            ->get('/dms-disk/file?path=docs/file.txt')
            ->assertStatus(200);
    }

    public function test_download_returns_404_for_missing_file(): void
    {
        $this->withHeaders($this->auth())
            ->get('/dms-disk/file?path=missing.txt')
            ->assertStatus(404);
    }

    // ── Exists ────────────────────────────────────────────────────────────────

    public function test_exists_returns_true(): void
    {
        Storage::put('test.txt', 'hi');

        $this->withHeaders($this->auth())
            ->getJson('/dms-disk/exists?path=test.txt')
            ->assertStatus(200)
            ->assertJson(['exists' => true]);
    }

    public function test_exists_returns_false(): void
    {
        $this->withHeaders($this->auth())
            ->getJson('/dms-disk/exists?path=ghost.txt')
            ->assertStatus(200)
            ->assertJson(['exists' => false]);
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_delete_removes_file(): void
    {
        Storage::put('remove-me.txt', 'bye');

        $this->withHeaders($this->auth())
            ->deleteJson('/dms-disk/file?path=remove-me.txt')
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        Storage::assertMissing('remove-me.txt');
    }

    // ── Move ──────────────────────────────────────────────────────────────────

    public function test_move_renames_file(): void
    {
        Storage::put('old.txt', 'content');

        $this->withHeaders($this->auth())
            ->postJson('/dms-disk/move', ['from' => 'old.txt', 'to' => 'new.txt'])
            ->assertStatus(200);

        Storage::assertMissing('old.txt');
        Storage::assertExists('new.txt');
    }

    public function test_move_returns_404_for_missing_source(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/dms-disk/move', ['from' => 'nowhere.txt', 'to' => 'somewhere.txt'])
            ->assertStatus(404);
    }

    // ── List ──────────────────────────────────────────────────────────────────

    public function test_list_returns_files(): void
    {
        Storage::put('docs/a.pdf', 'a');
        Storage::put('docs/b.pdf', 'b');

        $this->withHeaders($this->auth())
            ->getJson('/dms-disk/list?directory=docs')
            ->assertStatus(200)
            ->assertJsonCount(2, 'files');
    }

    public function test_list_recursive_returns_all_files(): void
    {
        Storage::put('docs/2024/a.pdf', 'a');
        Storage::put('docs/2025/b.pdf', 'b');

        $this->withHeaders($this->auth())
            ->getJson('/dms-disk/list?directory=docs&recursive=true')
            ->assertStatus(200)
            ->assertJsonCount(2, 'files');
    }

    // ── Metadata ──────────────────────────────────────────────────────────────

    public function test_metadata_returns_file_info(): void
    {
        Storage::put('meta.txt', 'hello');

        $this->withHeaders($this->auth())
            ->getJson('/dms-disk/metadata?path=meta.txt')
            ->assertStatus(200)
            ->assertJsonStructure(['path', 'size', 'mime_type', 'visibility', 'last_modified']);
    }

    public function test_metadata_returns_404_for_missing_file(): void
    {
        $this->withHeaders($this->auth())
            ->getJson('/dms-disk/metadata?path=ghost.txt')
            ->assertStatus(404);
    }

    // ── Visibility ────────────────────────────────────────────────────────────

    public function test_set_visibility_updates_file(): void
    {
        Storage::put('vis.txt', 'data');

        $this->withHeaders($this->auth())
            ->postJson('/dms-disk/visibility', ['path' => 'vis.txt', 'visibility' => 'public'])
            ->assertStatus(200)
            ->assertJson(['status' => 'ok']);
    }

    public function test_set_visibility_returns_404_for_missing_file(): void
    {
        $this->withHeaders($this->auth())
            ->postJson('/dms-disk/visibility', ['path' => 'ghost.txt', 'visibility' => 'public'])
            ->assertStatus(404);
    }
}

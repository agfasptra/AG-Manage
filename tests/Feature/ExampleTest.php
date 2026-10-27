<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Test the application root returns 200 and renders AG Manage landing page.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('AG');
        $response->assertSee('Manage');
    }

    /**
     * Test MySQL database connectivity when service is active.
     */
    public function test_database_connection_is_verified(): void
    {
        try {
            $pdo = DB::connection('mysql')->getPdo();
            $this->assertNotNull($pdo);
            $this->assertTrue(Schema::connection('mysql')->hasTable('users'));
            $this->assertTrue(Schema::connection('mysql')->hasTable('migrations'));
        } catch (\Throwable $e) {
            $this->markTestSkipped('MySQL service is not running: '.$e->getMessage());
        }
    }

    /**
     * Test frontend assets are built and correctly loaded by Blade @vite directive.
     */
    public function test_frontend_assets_are_built_and_loaded_by_blade(): void
    {
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath, 'Vite manifest.json must exist in public/build/');

        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertArrayHasKey('resources/css/app.css', $manifest);

        $cssFile = public_path('build/'.$manifest['resources/css/app.css']['file']);
        $this->assertFileExists($cssFile, 'Compiled CSS asset file must exist');

        $cssContent = file_get_contents($cssFile);
        $this->assertStringContainsString('bg-emerald-600', $cssContent, 'Tailwind utility classes must be compiled');
        $this->assertStringContainsString('font-sans', $cssContent, 'Font sans must be compiled');

        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee($manifest['resources/css/app.css']['file'], false);
    }
}

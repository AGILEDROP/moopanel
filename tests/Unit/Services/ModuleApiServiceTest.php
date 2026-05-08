<?php

namespace Tests\Unit\Services;

use App\Models\Instance;
use App\Services\ModuleApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ModuleApiServiceTest extends TestCase
{
    public function test_trigger_task_check_sends_expected_request(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 2], 200),
        ]);

        $service = new ModuleApiService();
        $service->triggerTaskCheck('https://example.test', 'secret', ['id' => 12, 'type' => 'core_update']);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://example.test/local/moopanel/server.php/tasks/check?id=12&type=core_update'
            && $request->hasHeader('X-API-KEY', 'secret'));
    }

    public function test_get_active_moodle_users_count_returns_online_count(): void
    {
        Http::fake([
            '*' => Http::response(['online' => 27], 200),
        ]);

        $count = (new ModuleApiService())->getActiveMoodleUsersCount(
            Instance::factory()->withApiKey('secret')->create(['url' => 'https://example.test']),
            10,
            20
        );

        $this->assertSame(27, $count);
    }

    public function test_get_active_moodle_users_count_logs_when_request_fails(): void
    {
        Log::spy();
        Http::fake([
            '*' => Http::response(['online' => null], 500),
        ]);

        (new ModuleApiService())->getActiveMoodleUsersCount(
            Instance::factory()->withApiKey('secret')->create(['url' => 'https://example.test']),
            10,
            20
        );

        Log::shouldHaveReceived('error')->once();
    }

    public function test_post_api_key_status_wraps_payload_in_data_envelope(): void
    {
        Http::fake();

        (new ModuleApiService())->postApiKeyStatus('https://example.test', 'secret', 1700000000);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://example.test/local/moopanel/server.php/api_key_status'
            && $request->hasHeader('X-API-KEY', 'secret')
            && $request->data() === ['data' => ['key_expiration_date' => 1700000000]]);
    }

    public function test_trigger_plugins_updates_posts_to_updates_endpoint(): void
    {
        Http::fake();

        (new ModuleApiService())->triggerPluginsUpdates('https://example.test', 'secret', ['plugins' => ['a']]);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://example.test/local/moopanel/server.php/plugins/updates'
            && $request->data() === ['data' => ['plugins' => ['a']]]);
    }

    public function test_trigger_plugin_zip_file_updates_posts_to_installzip_endpoint(): void
    {
        Http::fake();

        (new ModuleApiService())->triggerPluginZipFileUpdates('https://example.test', 'secret', ['file' => 'plugin.zip']);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://example.test/local/moopanel/server.php/plugins/installzip');
    }

    public function test_trigger_course_backup_uses_post_method(): void
    {
        Http::fake();

        (new ModuleApiService())->triggerCourseBackup('https://example.test', 'secret', ['course_id' => 1]);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://example.test/local/moopanel/server.php/backups');
    }

    public function test_trigger_course_backup_restore_uses_put_method(): void
    {
        Http::fake();

        (new ModuleApiService())->triggerCourseBackupRestore('https://example.test', 'secret', ['backup_id' => 1]);

        Http::assertSent(fn ($request) => $request->method() === 'PUT'
            && $request->url() === 'https://example.test/local/moopanel/server.php/backups');
    }

    public function test_trigger_course_backup_deletion_uses_delete_method(): void
    {
        Http::fake();

        (new ModuleApiService())->triggerCourseBackupDeletion('https://example.test', 'secret', ['backup_id' => 1]);

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && $request->url() === 'https://example.test/local/moopanel/server.php/backups');
    }

    public function test_get_courses_requests_categories_and_courses_query(): void
    {
        Http::fake([
            '*' => Http::response(['courses' => [], 'categories' => []], 200),
        ]);

        (new ModuleApiService())->getCourses('https://example.test', 'secret');

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), 'displaycategories')
            && str_contains($request->url(), 'displaycourses'));
    }

    public function test_get_instance_data_calls_root_module_path(): void
    {
        Http::fake();

        (new ModuleApiService())->getInstanceData('https://example.test', 'secret');

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://example.test/local/moopanel/server.php'
            && $request->hasHeader('X-API-KEY', 'secret'));
    }

    public function test_get_core_updates_decrypts_api_key_for_header(): void
    {
        Http::fake();

        $instance = Instance::factory()->withApiKey('decrypted-key')->create(['url' => 'https://example.test']);

        (new ModuleApiService())->getCoreUpdates($instance);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://example.test/local/moopanel/server.php/moodle_core'
            && $request->hasHeader('X-API-KEY', 'decrypted-key'));
    }

    public function test_trigger_core_update_posts_to_moodle_core_endpoint(): void
    {
        Http::fake();

        $instance = Instance::factory()->withApiKey('decrypted-key')->create(['url' => 'https://example.test']);

        (new ModuleApiService())->triggerCoreUpdate($instance, ['version' => 'next']);

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request->url() === 'https://example.test/local/moopanel/server.php/moodle_core'
            && $request->hasHeader('X-API-KEY', 'decrypted-key')
            && $request->data() === ['data' => ['version' => 'next']]);
    }

    public function test_get_plugins_uses_displayupdates_query(): void
    {
        Http::fake();

        (new ModuleApiService())->getPlugins('https://example.test', 'secret');

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), 'displayupdates')
            && str_contains($request->url(), 'displayupdateslog'));
    }

    public function test_get_active_moodle_users_count_passes_time_window_as_query(): void
    {
        Http::fake([
            '*' => Http::response(['online' => 5], 200),
        ]);

        (new ModuleApiService())->getActiveMoodleUsersCount(
            Instance::factory()->withApiKey('secret')->create(['url' => 'https://example.test']),
            100,
            200
        );

        Http::assertSent(fn ($request) => str_contains($request->url(), 'timeStart=100')
            && str_contains($request->url(), 'timeEnd=200'));
    }
}

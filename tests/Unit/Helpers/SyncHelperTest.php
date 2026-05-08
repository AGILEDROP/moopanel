<?php

namespace Tests\Unit\Helpers;

use App\Enums\Status;
use App\Enums\UpdateLogType;
use App\Enums\UpdateMaturity;
use App\Enums\UpdateType;
use App\Helpers\SyncHelper;
use App\Models\Category;
use App\Models\Course;
use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Update;
use App\Models\UpdateLog;
use Tests\TestCase;

class SyncHelperTest extends TestCase
{
    public function test_updates_crud_action_creates_new_updates_and_removes_stale_ones(): void
    {
        $instance = Instance::factory()->create();

        // Existing update that is no longer reported by the sync — should be deleted.
        $stale = Update::factory()->for($instance)->create([
            'version' => '1999010100',
            'release' => 'old',
        ]);

        (new SyncHelper())->updatesCrudAction([
            [
                'version' => '2024010100',
                'release' => '4.3.1',
                'maturity' => UpdateMaturity::STABLE->value,
                'type' => UpdateType::CORE_MINOR->value,
                'url' => 'https://example.test/update',
                'download' => 'https://example.test/update.zip',
                'downloadmd5' => md5('a'),
            ],
        ], $instance->id);

        $this->assertDatabaseMissing('updates', ['id' => $stale->id]);
        $this->assertDatabaseHas('updates', [
            'instance_id' => $instance->id,
            'plugin_id' => null,
            'version' => '2024010100',
            'release' => '4.3.1',
        ]);
    }

    public function test_updates_crud_action_keeps_only_plugin_scoped_rows_when_plugin_id_is_passed(): void
    {
        $instance = Instance::factory()->create();
        $plugin = Plugin::create([
            'name' => 'sample',
            'display_name' => 'Sample',
            'type' => 'mod',
            'component' => 'mod_sample_'.uniqid(),
            'is_standard' => true,
            'settings_section' => null,
            'directory' => '/mod/sample',
        ]);

        $coreUpdate = Update::factory()->for($instance)->create(['plugin_id' => null]);
        $existingPluginUpdate = Update::factory()->for($instance)->create([
            'plugin_id' => $plugin->id,
            'version' => '1900010100',
        ]);

        (new SyncHelper())->updatesCrudAction([
            [
                'version' => '2024010100',
                'release' => '4.3.1',
                'maturity' => UpdateMaturity::STABLE->value,
                'type' => UpdateType::CORE_MINOR->value,
                'url' => 'https://example.test/update',
                'download' => 'https://example.test/update.zip',
                'downloadmd5' => md5('b'),
            ],
        ], $instance->id, $plugin->id);

        // Core update with plugin_id = null is untouched, only plugin-scoped rows are pruned.
        $this->assertDatabaseHas('updates', ['id' => $coreUpdate->id]);
        $this->assertDatabaseMissing('updates', ['id' => $existingPluginUpdate->id]);
        $this->assertDatabaseHas('updates', [
            'instance_id' => $instance->id,
            'plugin_id' => $plugin->id,
            'version' => '2024010100',
        ]);
    }

    public function test_update_log_crud_action_upserts_and_prunes_per_plugin(): void
    {
        $instance = Instance::factory()->create();
        $plugin = Plugin::create([
            'name' => 'sample',
            'display_name' => 'Sample',
            'type' => 'mod',
            'component' => 'mod_sample_'.uniqid(),
            'is_standard' => true,
            'settings_section' => null,
            'directory' => '/mod/sample',
        ]);

        $stale = UpdateLog::create([
            'operation_id' => 99,
            'instance_id' => $instance->id,
            'plugin_id' => $plugin->id,
            'username' => 'old',
            'type' => UpdateLogType::Notice->value,
            'version' => '1900010100',
            'targetversion' => '1900010100',
            'info' => 'old',
            'details' => '',
            'backtrace' => '',
            'timemodified' => now()->subYear(),
        ]);

        (new SyncHelper())->updateLogCrudAction([
            [
                'id' => 1,
                'username' => 'admin',
                'type' => UpdateLogType::Success->value,
                'version' => '2024010100',
                'targetversion' => '2024020100',
                'timemodified' => now()->getTimestamp(),
                'info' => 'Plugin upgraded',
                'details' => '',
                'backtrace' => '',
            ],
        ], $instance->id, $plugin->id);

        $this->assertDatabaseMissing('update_logs', ['id' => $stale->id]);
        $this->assertDatabaseHas('update_logs', [
            'operation_id' => 1,
            'instance_id' => $instance->id,
            'plugin_id' => $plugin->id,
            'info' => 'Plugin upgraded',
        ]);
    }

    public function test_courses_crud_action_creates_categories_and_courses_with_parent_links(): void
    {
        $instance = Instance::factory()->create();

        (new SyncHelper())->coursesCrudAction([
            'categories' => [
                ['id' => 1, 'parent' => 0, 'name' => 'Root', 'depth' => 1, 'path' => '/1'],
                ['id' => 2, 'parent' => 1, 'name' => 'Child', 'depth' => 2, 'path' => '/1/2'],
            ],
            'courses' => [
                ['id' => 10, 'category' => 2, 'name' => 'Algebra'],
            ],
        ], $instance);

        $root = Category::where('instance_id', $instance->id)->where('moodle_category_id', 1)->first();
        $child = Category::where('instance_id', $instance->id)->where('moodle_category_id', 2)->first();

        $this->assertNotNull($root);
        $this->assertNotNull($child);
        $this->assertNull($root->parent_id);
        $this->assertSame($root->id, $child->parent_id);

        $course = Course::where('instance_id', $instance->id)->where('moodle_course_id', 10)->first();
        $this->assertNotNull($course);
        $this->assertSame($child->id, $course->category_id);
        $this->assertSame('Algebra', $course->name);
    }

    public function test_courses_crud_action_throws_when_payload_is_missing_keys(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing course and category data on course sync');

        (new SyncHelper())->coursesCrudAction([], Instance::factory()->create());
    }

    public function test_courses_crud_action_removes_categories_and_courses_no_longer_present(): void
    {
        $instance = Instance::factory()->create();
        $category = Category::create([
            'instance_id' => $instance->id,
            'moodle_category_id' => 50,
            'moodle_category_parent_id' => 0,
            'name' => 'Stale',
            'depth' => 1,
            'path' => '/50',
        ]);
        Course::create([
            'instance_id' => $instance->id,
            'category_id' => $category->id,
            'moodle_course_id' => 500,
            'name' => 'Stale course',
        ]);

        (new SyncHelper())->coursesCrudAction([
            'categories' => [
                ['id' => 1, 'parent' => 0, 'name' => 'Root', 'depth' => 1, 'path' => '/1'],
            ],
            'courses' => [
                ['id' => 11, 'category' => 1, 'name' => 'New course'],
            ],
        ], $instance);

        $this->assertDatabaseMissing('categories', ['moodle_category_id' => 50]);
        $this->assertDatabaseMissing('courses', ['moodle_course_id' => 500]);
        $this->assertDatabaseHas('courses', ['moodle_course_id' => 11, 'name' => 'New course']);
    }

    public function test_courses_crud_action_throws_when_course_category_is_missing(): void
    {
        $instance = Instance::factory()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/Category not found for course/');

        (new SyncHelper())->coursesCrudAction([
            'categories' => [
                ['id' => 1, 'parent' => 0, 'name' => 'Root', 'depth' => 1, 'path' => '/1'],
            ],
            'courses' => [
                ['id' => 12, 'category' => 999, 'name' => 'Orphan'],
            ],
        ], $instance);
    }

    public function test_instance_plugins_crud_action_syncs_pivot_and_propagates_nested_data(): void
    {
        $instance = Instance::factory()->create();

        (new SyncHelper())->instancePluginsCrudAction([
            [
                'plugin' => 'mod_quiz',
                'display_name' => 'Quiz',
                'plugintype' => 'mod',
                'component' => 'mod_quiz',
                'is_standard' => true,
                'settings_section' => null,
                'directory' => '/mod/quiz',
                'version' => '2024010100',
                'enabled' => true,
                'update_available' => [
                    [
                        'version' => '2024020100',
                        'release' => '4.3.2',
                        'maturity' => UpdateMaturity::STABLE->value,
                        'type' => UpdateType::PLUGIN->value,
                        'url' => 'https://example.test/plugin-update',
                        'download' => 'https://example.test/plugin-update.zip',
                        'downloadmd5' => md5('plugin'),
                    ],
                ],
                'update_log' => [
                    [
                        'id' => 5,
                        'username' => 'admin',
                        'type' => UpdateLogType::Success->value,
                        'version' => '2024010100',
                        'targetversion' => '2024020100',
                        'timemodified' => now()->getTimestamp(),
                        'info' => 'Plugin upgraded',
                        'details' => '',
                        'backtrace' => '',
                    ],
                ],
            ],
        ], $instance);

        $plugin = Plugin::where('component', 'mod_quiz')->first();
        $this->assertNotNull($plugin);
        $this->assertSame('Quiz', $plugin->display_name);

        $this->assertDatabaseHas('instance_plugin', [
            'instance_id' => $instance->id,
            'plugin_id' => $plugin->id,
            'version' => '2024010100',
            'enabled' => true,
        ]);

        $this->assertDatabaseHas('updates', [
            'plugin_id' => $plugin->id,
            'version' => '2024020100',
        ]);
        $this->assertDatabaseHas('update_logs', [
            'operation_id' => 5,
            'plugin_id' => $plugin->id,
        ]);
    }

    public function test_set_instance_status_to_disconnected_only_writes_when_status_changes(): void
    {
        $helper = new SyncHelper();
        $instance = Instance::factory()->create(['status' => Status::Connected]);

        $helper->setInstanceStatusToDisconnected($instance);
        $this->assertSame(Status::Disconnected, $instance->fresh()->status);

        // Calling again on an already-disconnected instance is a no-op (no extra updated_at bump).
        $disconnected = $instance->fresh();
        $previousUpdatedAt = $disconnected->updated_at;

        \Illuminate\Support\Carbon::setTestNow(now()->addMinutes(10));
        $helper->setInstanceStatusToDisconnected($disconnected);
        \Illuminate\Support\Carbon::setTestNow();

        $this->assertEquals($previousUpdatedAt->toDateTimeString(), $disconnected->fresh()->updated_at->toDateTimeString());
    }
}

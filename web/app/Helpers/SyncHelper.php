<?php

namespace App\Helpers;

use App\Enums\Status;
use App\Models\Category;
use App\Models\Course;
use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Update;
use App\Models\UpdateLog;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;

class SyncHelper
{
    public function updatesCrudAction(mixed $results, int $instanceId, ?int $pluginId = null): void
    {
        $availableUpdateIds = [];
        foreach ($results as $item) {
            $update = Update::withoutGlobalScopes()->updateOrCreate([
                'instance_id' => $instanceId,
                'plugin_id' => $pluginId,
                'version' => $item['version'],
                'release' => $item['release'],
                'maturity' => $item['maturity'],
            ], [
                'type' => $item['type'],
                'url' => $item['url'],
                'download' => $item['download'],
                'downloadmd5' => $item['downloadmd5'],
            ]);
            $availableUpdateIds[] = $update->id;
        }

        Update::where([
            ['instance_id', '=', $instanceId],
            ['plugin_id', '=', $pluginId],
        ])->whereNotIn('id', $availableUpdateIds)->delete();
    }

    public function updateLogCrudAction(mixed $results, int $instanceId, ?int $pluginId = null): void
    {
        $updatesIds = [];
        foreach ($results as $item) {
            $update = UpdateLog::withoutGlobalScopes()->updateOrCreate(
                [
                    'operation_id' => $item['id'],
                    'instance_id' => $instanceId,
                    'plugin_id' => $pluginId,
                ],
                [
                    'username' => $item['username'],
                    'type' => $item['type'],
                    'version' => $item['version'],
                    'targetversion' => $item['targetversion'],
                    'timemodified' => Carbon::createFromTimestamp($item['timemodified'])->rawFormat('Y-m-d H:i:s'),
                    'info' => $item['info'],
                    'details' => $item['details'],
                    'backtrace' => $item['backtrace'],
                ]
            );
            $updatesIds[] = $update->id;
        }

        UpdateLog::where([
            ['instance_id', '=', $instanceId],
            ['plugin_id', '=', $pluginId],
        ])->whereNotIn('id', $updatesIds)->delete();
    }

    public function coursesCrudAction(mixed $results, Model|Instance $instance): void
    {
        if (empty($results) || ! isset($results['categories']) || ! isset($results['courses'])) {
            throw new Exception('Missing course and category data on course sync');

            return;
        }

        // Sort by depth to avoid foreign key constraint violation
        $categories = collect($results['categories'])->sortBy('depth');

        // There can be multiple root categories in moodle...they all have parent_id = 0
        // Moodle root level depth = 1, parent_id = 0

        foreach ($categories as $category) {
            Category::updateOrCreate(
                [
                    'instance_id' => $instance->id,
                    'moodle_category_id' => $category['id'],
                ],
                [
                    'moodle_category_id' => $category['id'],
                    'moodle_category_parent_id' => $category['parent'],
                    'name' => $category['name'],
                    'depth' => $category['depth'],
                    'path' => $category['path'],

                    // Temporarily skip setting 'parent_id' to avoid foreign key constraint violation
                ]
            );
        }

        // Update parents
        foreach ($categories as $category) {
            $parentCategory = null;

            if ($category['parent'] > 0) {
                $parentCategory = Category::where('moodle_category_id', $category['parent'])
                    ->where('instance_id', $instance->id)
                    ->first();
            }

            $currentCategory = Category::where('moodle_category_id', $category['id'])
                ->where('instance_id', $instance->id)
                ->first();

            $currentCategory->update([
                'parent_id' => $parentCategory ? $parentCategory->id : null,
            ]);
        }

        // remove deleted categories
        Category::where('instance_id', $instance->id)
            ->whereNotIn('moodle_category_id', array_column($results['categories'], 'id'))
            ->delete();

        // Sync courses
        $courses = collect($results['courses']);

        foreach ($courses as $course) {
            $category = Category::where('instance_id', $instance->id)
                ->where('moodle_category_id', $course['category'])
                ->first();

            if (! $category) {
                throw new Exception("Category not found for course {$course['id']} on course sync");
            }

            Course::updateOrCreate(
                [
                    'instance_id' => $instance->id,
                    'moodle_course_id' => $course['id'],
                ],
                [
                    'category_id' => $category->id,
                    'name' => $course['name'],
                ]
            );
        }

        // remove deleted courses
        Course::where('instance_id', $instance->id)
            ->whereNotIn('moodle_course_id', array_column($results['courses'], 'id'))
            ->delete();
    }

    public function instancePluginsCrudAction(mixed $results, Model|Instance $instance): void
    {
        $pluginPivotData = [];
        foreach ($results as $item) {
            $plugin = Plugin::withoutGlobalScopes()->updateOrCreate(
                [
                    'component' => $item['component'],
                ],
                [
                    'name' => $item['plugin'],
                    'display_name' => $item['display_name'],
                    'type' => $item['plugintype'],
                    'is_standard' => $item['is_standard'],
                    'settings_section' => $item['settings_section'],
                    'directory' => $item['directory'],
                ]
            );

            if (isset($item['update_available'])) {
                $this->updatesCrudAction($item['update_available'], $instance->id, $plugin->id);
            }

            if (isset($item['update_log'])) {
                $this->updateLogCrudAction($item['update_log'], $instance->id, $plugin->id);
            }

            $pluginPivotData[$plugin->id] = [
                'version' => $item['version'],
                'enabled' => $item['enabled'],
            ];
        }

        $instance->plugins()->sync($pluginPivotData);
    }

    public function setInstanceStatusToDisconnected(Instance $instance): void
    {
        if ($instance->status !== Status::Disconnected) {
            $instance->update(['status' => Status::Disconnected]);
        }
    }
}

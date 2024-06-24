<?php

namespace App\Jobs\Update;

use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ZipPluginDeleteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $pendingZipPluginUpdateRequestItems = UpdateRequestItem::join('update_requests', 'update_requests.id', '=', 'update_request_items.update_request_id')
            ->select(
                'update_request_items.id as id',
                'update_request_items.zip_path as zip_path'
            )
            ->where('update_request_items.status', UpdateRequestItem::STATUS_PENDING)
            ->where('update_requests.type', UpdateRequest::TYPE_PLUGIN_ZIP)
            ->get();


        // Get all files in storage/public/zip_files
        $zipFiles = Storage::files('zip_updates');

        $filesToDelete = [];
        foreach ($zipFiles as $key => $filePath) {

            $isCurrentFileUsedInPendingUpdate = false;
            foreach ($pendingZipPluginUpdateRequestItems as $item) {
                if (str_contains($item->zip_path, $filePath)) {

                    Log::info("not deleting $filePath");

                    $isCurrentFileUsedInPendingUpdate = true;
                    break;
                }
            }

            if (!$isCurrentFileUsedInPendingUpdate) {
                $filesToDelete[] = $filePath;
            }
        }

        try {
            foreach ($filesToDelete as $path) {
                Storage::delete($path);
                Log::info("Deleted file: {$filePath} as it is not part of any pending update requests.");
            }
        } catch (Exception $e) {
            Log::error('Failed to delete zip file: ' . $path . ' Error message: ' . $e->getMessage());
        }
    }
}

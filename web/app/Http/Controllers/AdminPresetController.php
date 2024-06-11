<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminPresetCreate;
use App\Models\DeltaReport;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleXMLElement;

class AdminPresetController extends Controller
{
    public function store(AdminPresetCreate $request, $instance_id)
    {
        // Raw XML content
        $rawXml = $request->getContent();

        // Write the raw XML to a file in the AdminPresets directory
        $filePath = "admin_presets/{$instance_id}.xml";
        Storage::disk('local')->put($filePath, $rawXml);

        // Update the instance configuration_path
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->find((int) $instance_id);
        $instance->configuration_path =  $filePath;
        $instance->save();

        // Find all delta reports that are waiting for the instance configuration

        $deltaReports = DeltaReport::where(function ($query) use ($instance) {
            $query->where('first_instance_id', $instance->id)
                ->where('first_instance_config_received', false);
        })->orWhere(function ($query) use ($instance) {
            $query->where('second_instance_id', $instance->id)
                ->where('second_instance_config_received', false);
        })->get();

        // Mark the instance configuration as received in the delta reports
        $deltaReports = $deltaReports->map(function ($deltaReport) use ($instance) {
            if ($deltaReport->first_instance_id == $instance->id) {
                $deltaReport->first_instance_config_received = true;
            } else {
                $deltaReport->second_instance_config_received = true;
            }

            $deltaReport->save();

            // Check if both instances have sent their configuration
            if ($deltaReport->first_instance_config_received && $deltaReport->second_instance_config_received) {
                Notification::make()
                    ->success()
                    ->title(__('Delta report generated'))
                    ->body(__(':name report has been generated. Click on the button below, to view it.', ['name' => $deltaReport->name]))
                    ->actions([
                        Action::make('view')
                            ->color('success')
                            ->button()
                            ->url(route('filament.admin.pages.delta-reports', ['delta_report_id' => $deltaReport->id]), shouldOpenInNewTab: true),
                        Action::make('cancel')
                            ->color('secondary')
                            ->close(),
                    ])
                    ->icon('heroicon-o-document-text')
                    ->iconColor('success')
                    ->sendToDatabase(User::find($deltaReport->user_id));
            }

            return $deltaReport;
        });

        return response()->json(
            ['message' => __('Admin preset stored successfully')],
            200
        );
    }
}

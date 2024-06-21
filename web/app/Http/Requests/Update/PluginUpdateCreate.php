<?php

namespace App\Http\Requests\Update;

use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PluginUpdateCreate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $this->validateInstanceId();

        // Authentication is performed in the middleware
        return true;
    }

    /**
     * Validate instance_id
     */
    private function validateInstanceId(): void
    {
        $instanceId = $this->route('instance_id');

        if ($instanceId === null) {
            throw ValidationException::withMessages([
                'instance_id' => 'Missing instance_id',
            ]);
        }

        if (!Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instanceId)->exists()) {
            throw ValidationException::withMessages([
                'instance_id' => 'Invalid instance_id',
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'username' => 'nullable|string|email',
            'moodle_job_id' => 'required|integer',
            'updates' => 'required|array|min:1',
            'updates.*.model_id' => 'required|integer|exists:updates,id',
            'updates.*.status' => 'required|boolean',
            'updates.*.component' => 'required|string',
            'updates.*.error' => 'nullable|string',
        ];
    }
}

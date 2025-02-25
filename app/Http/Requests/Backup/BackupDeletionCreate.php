<?php

namespace App\Http\Requests\Backup;

use App\Models\BackupResult;
use App\Traits\ValidatesInstanceId;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class BackupDeletionCreate extends FormRequest
{
    use ValidatesInstanceId;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $this->validateInstanceId();

        // request is already authorized by middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'backup_result_id' => [
                'required',
                'integer',
                'exists:backup_results,id',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! BackupResult::where('id', $value)->where('deleted_at', null)->where('in_deletion_process', true)->exists()) {
                        $fail('The backup result does not exist or is already deleted.');
                    }
                },
            ],
            'status' => 'required|boolean',
            'message' => 'nullable|string',
        ];
    }
}

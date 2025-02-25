<?php

namespace App\Http\Requests\Update;

use App\Models\Update;
use App\Traits\ValidatesInstanceId;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class CoreUpdateCreate extends FormRequest
{
    use ValidatesInstanceId;

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
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'moodle_job_id' => 'required|integer',
            'status' => 'required|boolean',
            'message' => 'required|string',
            'update_id' => [
                'required',
                'integer',
                'exists:updates,id',
                // Check if the updateId is connected to instance
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! Update::where('id', $value)->where('instance_id', $this->route('instance_id'))->exists()) {
                        $fail('Core update ID does not belong to the instance.');
                    }
                },
            ],
        ];
    }
}

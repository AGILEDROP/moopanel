<?php

namespace App\Http\Requests\Update;

use App\Traits\ValidatesInstanceId;
use Illuminate\Foundation\Http\FormRequest;

class PluginUpdateCreate extends FormRequest
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

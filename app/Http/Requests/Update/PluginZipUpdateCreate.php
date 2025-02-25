<?php

namespace App\Http\Requests\Update;

use App\Traits\ValidatesInstanceId;
use Illuminate\Foundation\Http\FormRequest;

class PluginZipUpdateCreate extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'moodle_job_id' => ['required', 'integer'],
            'username' => ['nullable', 'email'],
            'updates' => ['required', 'array'],
            'updates.*.link' => ['required', 'string'],
            'updates.*.status' => ['required', 'boolean'],
            'updates.*.component' => ['required', 'string'],
            'updates.*.version' => ['nullable', 'string'],
            'updates.*.error' => ['nullable', 'string'],
        ];
    }
}

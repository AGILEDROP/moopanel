<?php

namespace App\Http\Requests\Backup;

use App\Models\Course;
use App\Traits\ValidatesInstanceId;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class CourseBackupCreate extends FormRequest
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
            'courseid' => [
                'required',
                'integer',
                'exists:courses,moodle_course_id',
                // Check if the course belongs to the instance
                function (string $attribute, mixed $value, Closure $fail) {
                    if (! Course::where('moodle_course_id', $value)->where('instance_id', $this->route('instance_id'))->exists()) {
                        $fail('The course does not belong to the instance.');
                    }
                },
            ],
            'link' => 'required|url',
            'password' => 'required|string',
            'status' => 'required|boolean',
            'filesize' => 'nullable|integer',
        ];
    }
}

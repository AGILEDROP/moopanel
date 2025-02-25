<?php

namespace App\Http\Requests;

use App\Traits\ValidatesInstanceId;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use SimpleXMLElement;

class AdminPresetCreate extends FormRequest
{
    use ValidatesInstanceId;

    protected function validateXml()
    {
        if (empty($this->getContent())) {
            throw ValidationException::withMessages([
                'message' => 'Empty XML content',
            ]);
        }

        if ($this->header('Content-Type') == 'application/xml') {
            $dom = new \DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadXML($this->getContent());
            $errors = libxml_get_errors();
            libxml_clear_errors();

            if (! empty($errors)) {
                throw ValidationException::withMessages([
                    'message' => 'Invalid XML format',
                    'errors' => $errors,
                ]);
            }

            /* $xml = new SimpleXMLElement($this->getContent());
            $data = json_decode(json_encode((array)$xml), true);
            $this->merge($data); */
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $this->validateInstanceId();
        $this->validateXml();

        // Authentication is already set on route middleware
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}

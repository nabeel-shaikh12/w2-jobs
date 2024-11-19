<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAiTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:1000'],
            'preview' => ['nullable', 'image', 'max:2048'],
            'icon' => ['nullable', 'image', 'max:2048'],
            'type' => ['required'],
            'status' => ['required'],
            'ai_model' => ['required'],
            'fields.*.type' => ['required'],
            'fields.*.name' => ['required'],
            'fields.*.placeholder' => ['required'],
            'fields.*.value' => ['nullable'],
            // 'lang' => ['required'],
            'prompt' => ['required'],
        ];
    }

    public function attributes()
    {
        return [
            'fields.*.type' => 'type',
            'fields.*.name' => 'name',
            'fields.*.placeholder' => 'placeholder',
        ];
    }
}

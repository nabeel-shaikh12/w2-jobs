<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreProfileSetupRequest2 extends FormRequest
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

        if ($this->submit_type == 'video_intro') {
            return ['video_intro' => ['required', 'regex:%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i']];
        }

        if ($this->submit_type == 'resume') {
            return ['resume' => ['required', File::types(['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'])->max(5000)]];
        }

        // otherwise

        $category = Category::find($this->category_id);
        $skills = $category?->childrenCategories()->count() ?? null;

        return [
            'overview' => ['required', 'string', 'max:1000'],
            'gender' => ['required', 'string', 'in:male,female,other'],
            'date_of_birth' => ['required', 'date'],
            'avatar' => ['nullable', File::image()->max(2048)],
            'phone' => ['required', 'string', 'min:10', 'max:16'],

            'country_id' => ['required'],
            'state_id' => ['required'],
            'address' => ['required', 'string', 'max:255'],

            'service_id' => ['required'],
            'category_id' => ['required'],
            'skills' => [$skills ? 'required' : 'nullable', 'array'],
            'expert_level' => ['required'],
            'expected_salary' => ['nullable', 'numeric'],

            'work_experiences.*.from' => ['required'],
            'work_experiences.*.to' => ['required_if:work_experiences.*.is_current,false', 'nullable'],
            'work_experiences.*.designation' => ['required', 'string', 'max:255'],
            'work_experiences.*.org_name' => ['required', 'string', 'max:255'],
            'work_experiences.*.responsibilities' => ['nullable', 'string', 'max:500'],

            'education_qualifications' => ['nullable', 'array'],
            'education_qualifications.*.degree' => ['required'],
            'education_qualifications.*.institute_name' => ['required', 'string', 'max:100'],
            'education_qualifications.*.result' => ['required_if:education_qualifications.*.is_current,false', 'nullable', 'max:5.00'],
            'education_qualifications.*.start_at' => ['required', 'max_digits:4'],
            'education_qualifications.*.pass_year' => ['required_if:education_qualifications.*.is_current,false', 'nullable', 'after:education_qualifications.*.start_at', 'max_digits:4'],
        ];
    }

    public function messages(): array
    {
        return [
            'skills.required' => 'Please choose skills',
            'work_experiences.*.to.required_if' => 'The to field is required',
            'education_qualifications.*.pass_year' => 'Enter a valid passing year',
            'education_qualifications.*.result' => 'Enter a valid result',
        ];
    }

    public function attributes()
    {
        return [
            'country_id' => 'country',
            'state_id' => 'state',

            'work_experiences.*.is_current' => 'currently working',
            'work_experiences.*.from' => 'from',
            'work_experiences.*.to' => 'to',
            'work_experiences.*.designation' => 'designation',
            'work_experiences.*.org_name' => 'organization name',
            'work_experiences.*.responsibilities' => 'responsibilities',

            'education_qualifications.*.is_current' => 'currently studying',
            'education_qualifications.*.degree' => 'degree',
            'education_qualifications.*.institute_name' => 'institute name',
            'education_qualifications.*.result' => 'result',
            'education_qualifications.*.pass_year' => 'passing year',
            'education_qualifications.*.start_at' => 'start year',
        ];
    }
}

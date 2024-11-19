<?php

namespace App\Http\Requests;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreProfileSetupRequest extends FormRequest
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
        $step = $this->step;

        if ($step == 1) {
            return [
                'overview' => ['required', 'string', 'max:1000'],
                'gender' => ['required', 'string', 'in:male,female,other'],
                'date_of_birth' => ['required', 'before:today'],
                'avatar' => ['nullable', File::image()->max(2048)],
                'phone' => ['required', 'string', 'between:8,20'],
            ];
        }

        if ($step == 2) {
            return [
                'country_id' => ['required'],
                'state_id' => ['required'],
                'address' => ['required', 'string', 'max:255'],
            ];
        }

        if ($step == 3) {

            $category = Category::find($this->category_id);
            $skills = $category && $category->childrenCategories()->count() ? $category->childrenCategories()->count() : null;
            $currencies = collect(json_decode(file_get_contents(base_path('database/json/currencies.json')), true))
                ->pluck('code')
                ->toArray();
            return [
                'service_id' => ['required'],
                'category_id' => ['required'],
                'skills' => [$skills ? 'required' : 'nullable', 'array'],
                'expert_level' => ['required'],
                'expected_salary' => ['nullable', 'numeric'],
                'currency' => ['nullable', Rule::in($currencies), 'string'],

                'work_experiences.*.from' => ['required', 'date'],
                'work_experiences.*.to' => ['required_if:work_experiences.*.is_current,false', 'nullable', 'date', 'after:work_experiences.*.from'],
                'work_experiences.*.designation' => ['required', 'string', 'max:255'],
                'work_experiences.*.org_name' => ['required', 'string', 'max:255'],
                'work_experiences.*.responsibilities' => ['nullable', 'string', 'max:500'],
            ];
        }
        if ($step == 4) {
            return [
                'education_qualifications' => ['nullable', 'array'],
                'education_qualifications.*.degree' => ['required'],
                'education_qualifications.*.institute_name' => ['required', 'string', 'max:100'],
                'education_qualifications.*.result' => ['required_if:education_qualifications.*.is_current,false', 'nullable', 'max:5.00'],
                'education_qualifications.*.start_at' => ['required', 'max_digits:4'],
                'education_qualifications.*.pass_year' => ['required_if:education_qualifications.*.is_current,false', 'nullable', 'after:education_qualifications.*.start_at', 'max_digits:4'],
            ];
        }
        if ($step == 5) {
            return [
                'resume' => ['nullable','mimes:jpg,jpeg,pdf,doc,docx','max:5000' ],
            ];
        }
        return [];
    }

    public function messages(): array
    {
        $step = $this->step;

        if ($step == 3) {
            return [
                'skills.required' => 'Please choose skills',
                'work_experiences.*.to.required_if' => 'This field is required',
            ];
        }

        if ($step == 4) {
            return [
                'education_qualifications.*.pass_year' => 'Enter a valid passing year',
                'education_qualifications.*.result' => 'Enter a valid result',
            ];
        }
        return [];
    }

    public function attributes()
    {
        $step = $this->step;

        if ($step == 2) {
            return [
                'country_id' => 'country',
                'state_id' => 'state',
            ];
        }

        if ($step == 3) {
            return [
                'work_experiences.*.is_current' => 'currently working',
                'work_experiences.*.from' => 'from',
                'work_experiences.*.to' => 'to',
                'work_experiences.*.designation' => 'designation',
                'work_experiences.*.org_name' => 'organization name',
                'work_experiences.*.responsibilities' => 'responsibilities',
            ];
        }
        if ($step == 4) {
            return [
                'education_qualifications.*.is_current' => 'currently studying',
                'education_qualifications.*.degree' => 'degree',
                'education_qualifications.*.institute_name' => 'institute name',
                'education_qualifications.*.result' => 'result',
                'education_qualifications.*.pass_year' => 'passing year',
                'education_qualifications.*.start_at' => 'start year',
            ];
        }

        return [];
    }
}

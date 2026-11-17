<?php

namespace App\Http\Requests;

use App\Support\ScheduleTime;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('manage-schedules') === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject_name' => ['required', 'string', 'max:255'],
            'teacher_id' => ['required', 'integer', 'exists:teachers,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'room' => ['required', 'string', 'max:50'],
            'day_of_week' => ['required', 'integer', 'between:1,7'],
            'start_period' => ['required', 'integer', 'between:1,'.ScheduleTime::LAST_PERIOD],
            'end_period' => ['required', 'integer', 'between:1,'.ScheduleTime::LAST_PERIOD, 'gte:start_period'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Hãy nhập hoặc chọn :attribute.',
            'integer' => ':Attribute phải là số nguyên.',
            'string' => ':Attribute phải là văn bản.',
            'max.string' => ':Attribute được nhập tối đa :max ký tự.',
            'between.numeric' => ':Attribute phải từ :min đến :max.',
            'exists' => ':Attribute đã chọn không còn tồn tại.',
            'end_period.gte' => 'Tiết kết thúc phải bằng hoặc sau tiết bắt đầu.',
        ];
    }

    public function attributes(): array
    {
        return [
            'subject_name' => 'tên học phần',
            'teacher_id' => 'giảng viên',
            'branch_id' => 'cơ sở',
            'room' => 'phòng học',
            'day_of_week' => 'thứ trong tuần',
            'start_period' => 'tiết bắt đầu',
            'end_period' => 'tiết kết thúc',
        ];
    }
}

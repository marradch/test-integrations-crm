<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactFormRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'accountName' => ['required', 'string', 'regex:/^[\p{L}\s\'-]+$/u'],
            'accountPhone' => ['required', 'string', 'regex:/^\+380(?:39|50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)\d{7}$/'],
            'hiddenWebsite' => ['nullable', 'string'],
            'submittedAt' => ['required', 'integer'],
        ];
    }

    public function messages()
    {
        return [
            'accountName.required' => 'Name is required',
            'accountName.regex' => 'Name must contain only letters',
            'accountPhone.required' => 'Phone is required',
            'accountPhone.regex' => 'Phone must be a valid Ukrainian mobile number',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->filled('hiddenWebsite')) {
                $validator->errors()->add('hiddenWebsite', 'Spam detected. Please do not fill hidden fields.');
            }

            $submittedAt = (int) $this->input('submittedAt', 0);
            if (microtime(true) * 1000 - $submittedAt < 2000) {
                $validator->errors()->add('submittedAt', 'Please wait a moment before submitting the form.');
            }
        });
    }
}

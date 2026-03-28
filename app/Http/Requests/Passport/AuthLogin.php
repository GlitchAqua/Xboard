<?php

namespace App\Http\Requests\Passport;

use Illuminate\Foundation\Http\FormRequest;

class AuthLogin extends FormRequest
{
    public function rules()
    {
        return [
            'email' => 'required|string',
            'password' => 'required|min:8'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => __('Username or email can not be empty'),
            'password.required' => __('Password can not be empty'),
            'password.min' => __('Password must be greater than 8 digits')
        ];
    }
}

<?php

namespace App\Http\Requests\Passport;

use Illuminate\Foundation\Http\FormRequest;

class AuthRegister extends FormRequest
{
    public function rules()
    {
        return [
            'username' => 'required|string|min:3|max:64|regex:/^[a-zA-Z0-9_]+$/|unique:v2_user,username',
            'email' => 'nullable|email:strict',
            'password' => 'required|min:8'
        ];
    }

    public function messages()
    {
        return [
            'username.required' => __('Username can not be empty'),
            'username.min' => __('Username must be at least 3 characters'),
            'username.max' => __('Username must be less than 64 characters'),
            'username.regex' => __('Username can only contain letters, numbers and underscores'),
            'username.unique' => __('Username already exists'),
            'email.email' => __('Email format is incorrect'),
            'password.required' => __('Password can not be empty'),
            'password.min' => __('Password must be greater than 8 digits')
        ];
    }
}

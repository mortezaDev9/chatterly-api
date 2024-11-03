<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'username'              => ['required', 'string', 'unique:users,username', 'max:255'],
            'email'                 => ['required', 'email', 'unique:users,email', 'max:255'],
            'password'              => ['required', 'string', 'confirmed', Password::defaults(), 'max:255'],
            'password_confirmation' => ['required', 'string', 'max:255'],
            'agreement'             => ['required', 'accepted'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ! auth()->check();
    }
}

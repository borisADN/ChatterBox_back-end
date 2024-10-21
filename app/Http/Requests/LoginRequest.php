<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            'email' =>'required|email|exists:users,email',
            'password' =>'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Le champ email est requis.',
            'email.email' => 'Le champ email doit être un email valide.',
            'email.exists' => 'Cet email n\'est pas utilisé.',
            'password.required' => 'Le champ mot de passe est requis.',
            'password.min' => 'Le champ mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];
    }
}

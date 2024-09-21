<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
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
         
            'name' => 'required|string|max:255|min:5',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string|same:password',
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le champ nom est requis.',
            'name.min' => 'Le champ nom doit contenir au moins 5 caractères.',
            'email.required' => 'Le champ email est requis.',
            'email.unique' => 'Cet email est déjà utilisé.',
            'password.required' => 'Le champ mot de passe est requis.',
            'password.min' => 'Le champ mot de passe doit contenir au moins 8 caractères.',
            'password_confirmation.required' => 'Le champ mot de passe de confirmation est requis.',
            'password_confirmation.same' => 'Les mots de passe ne correspondent pas.',
            'avatar.image' => 'Le champ avatar doit être une image.',
            'avatar.mimes' => 'Le format de l\'avatar doit être jpeg, png, jpg, gif, ou svg.',
            'avatar.max' => 'L\'avatar ne doit pas dépasser 2 Mo.',
        ];
    }
}

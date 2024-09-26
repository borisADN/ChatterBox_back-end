<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendGroupMessageRequest extends FormRequest
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
            'group_id' => 'required|integer|exists:groups,id',
            'sender_id' => 'required|integer|exists:users,id',
            'content' =>'required|string|max:255',
            'image' => 'nullable|image|max:1024',
        
        ];
    }
}
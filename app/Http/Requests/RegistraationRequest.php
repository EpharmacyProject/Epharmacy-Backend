<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistraationRequest extends FormRequest
{
   
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

            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['required', 'email:filter', Rule::unique('users', 'email')],
            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',     
                'regex:/[a-z]/',      
                'regex:/[0-9]/',      
                'regex:/[@$!%*?&]/', 
                'confirmed',
            ],
            'is_role' => ['nullable', 'integer', Rule::in([0, 1, 2])], 
            'pharmacy_name' => ['nullable', 'string', 'max:255'],

            
            'address' => ['nullable', 'string', 'max:255'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'], 
            'lng' => ['nullable', 'numeric', 'between:-180,180'], 

        

           
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'], 

           
            'license_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'], 
        ];
    }

    
    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'pharmacy_name.required' => 'The Pharmacy name field is required.',
            'name.min' => 'The name must be at least 2 characters.',
            'email.required' => 'The email field is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email is already registered.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters long.',
            'password.confirmed' => 'The password confirmation does not match.',
            'is_role.in' => 'Invalid role selection.',

            'address.max' => 'The address should not exceed 255 characters.',
            'lat.between' => 'Invalid latitude value.',
            'lng.between' => 'Invalid longitude value.',

            'prescription_image.image' => 'The prescription must be an image file.',
            'prescription_image.mimes' => 'The prescription must be a JPEG, PNG, or JPG file.',
            'prescription_image.max' => 'The prescription image should not exceed 2MB.',

            'phone.regex' => 'The phone number must be between 10 and 15 digits.',
            'license_image.image' => 'The license image must be an image file.',
            'license_image.mimes' => 'The license image must be a JPEG, PNG, or JPG file.',
            'license_image.max' => 'The license image should not exceed 2MB.',
        ];
    }
}

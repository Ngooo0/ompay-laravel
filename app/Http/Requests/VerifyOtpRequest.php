<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
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
            'phone' => 'required|string|exists:clients,telephone',
            'otp_code' => 'required|string|size:6',
        ];
    }

    /**
     * Préparer les données avant la validation.
     * Normalise le téléphone pour que la règle `exists:clients,telephone` fonctionne
     * quel que soit le format envoyé par le client.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $p = preg_replace('/[^0-9+]/', '', $this->input('phone'));
            if (strlen($p) === 9 && $p[0] !== '+') {
                $p = '+221' . $p;
            }
            if (strlen($p) === 10 && $p[0] === '0') {
                $p = '+221' . substr($p, 1);
            }
            $this->merge(['phone' => $p]);
        }
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Le téléphone est requis.',
            'phone.exists' => 'Aucun client trouvé avec ce téléphone.',
            'otp_code.required' => 'Le code OTP est requis.',
            'otp_code.size' => 'Le code OTP doit contenir 6 caractères.',
        ];
    }
}

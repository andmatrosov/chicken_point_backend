<?php

namespace App\Http\Requests;

class RegisterRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:'.(int) config('game.auth.password_min_length', 8)],
            'device_id' => ['nullable', 'string', 'max:191'],
            'platform' => ['nullable', 'string', 'max:32'],
            'app_version' => ['nullable', 'string', 'max:32'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge([
                'email' => mb_strtolower(trim((string) $this->input('email'))),
            ]);
        }
    }
}

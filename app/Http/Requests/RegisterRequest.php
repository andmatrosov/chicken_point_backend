<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

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
            'email' => ['required', 'string', 'email:filter', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'confirmed', 'min:'.(int) config('game.auth.password_min_length', 8)],
            'device_id' => ['required', 'string', 'max:191'],
            'platform' => ['required', 'string', Rule::in(['ios', 'android'])],
            'app_version' => ['required', 'string', 'max:32'],
        ];
    }

    /**
     * @return array{
     *     email: string,
     *     password: string,
     *     device_context: array{
     *         device_id: string,
     *         platform: string,
     *         app_version: string
     *     }
     * }
     */
    public function payload(): array
    {
        return [
            'email' => $this->string('email')->toString(),
            'password' => $this->string('password')->toString(),
            'device_context' => $this->deviceContext(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->normalizeLowercaseStringInput('email'),
            'device_id' => $this->normalizeStringInput('device_id'),
            'platform' => $this->normalizeLowercaseStringInput('platform'),
            'app_version' => $this->normalizeStringInput('app_version'),
        ]);
    }

    /**
     * @return array{device_id: string, platform: string, app_version: string}
     */
    protected function deviceContext(): array
    {
        return [
            'device_id' => $this->string('device_id')->toString(),
            'platform' => $this->string('platform')->toString(),
            'app_version' => $this->string('app_version')->toString(),
        ];
    }
}

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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
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
            'email' => $this->filled('email')
                ? mb_strtolower(trim((string) $this->input('email')))
                : $this->input('email'),
            'device_id' => $this->normalizeStringInput('device_id'),
            'platform' => $this->filled('platform')
                ? mb_strtolower(trim((string) $this->input('platform')))
                : $this->input('platform'),
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

    protected function normalizeStringInput(string $key): mixed
    {
        if (! $this->exists($key)) {
            return $this->input($key);
        }

        return is_string($this->input($key))
            ? trim((string) $this->input($key))
            : $this->input($key);
    }
}

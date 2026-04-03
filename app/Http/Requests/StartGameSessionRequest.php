<?php

namespace App\Http\Requests;

class StartGameSessionRequest extends ApiFormRequest
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
            'metadata' => ['nullable', 'array'],
            'metadata.device_id' => ['nullable', 'string', 'max:191'],
            'metadata.app_version' => ['nullable', 'string', 'max:32'],
            'metadata.platform' => ['nullable', 'string', 'max:32'],
        ];
    }
}

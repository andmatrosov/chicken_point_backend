<?php

namespace App\Http\Requests;

class SubmitScoreRequest extends ApiFormRequest
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
            'session_token' => ['required', 'string'],
            'score' => ['required', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'metadata.duration' => ['nullable', 'integer'],
            'metadata.coins_collected' => ['nullable', 'integer', 'min:0'],
            'metadata.app_version' => ['nullable', 'string', 'max:32'],
            'metadata.device_id' => ['nullable', 'string', 'max:191'],
            'metadata.anti_fraud' => ['nullable', 'array'],
        ];
    }
}

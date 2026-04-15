<?php

namespace App\Http\Requests;

use App\Http\Payloads\Game\CloseGameSessionPayload;

class CloseGameSessionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'session_token' => ['required', 'string'],
        ];
    }

    public function payload(): CloseGameSessionPayload
    {
        return new CloseGameSessionPayload(
            sessionToken: $this->string('session_token')->toString(),
        );
    }
}

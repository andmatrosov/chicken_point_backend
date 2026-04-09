<?php

namespace App\Http\Requests;

use App\Http\Payloads\Game\StartGameSessionPayload;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StartGameSessionRequest extends ApiFormRequest
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
            'metadata' => ['nullable', 'array'],
            'metadata.device_id' => ['nullable', 'string', 'max:191'],
            'metadata.app_version' => ['nullable', 'string', 'max:32'],
            'metadata.platform' => ['nullable', 'string', Rule::in(['ios', 'android'])],
        ];
    }

    public function payload(): StartGameSessionPayload
    {
        return new StartGameSessionPayload(
            metadata: (array) $this->input('metadata', []),
        );
    }

    protected function prepareForValidation(): void
    {
        $metadata = $this->input('metadata');

        if (! is_array($metadata)) {
            return;
        }

        $normalizedMetadata = $metadata;

        foreach (['device_id', 'app_version'] as $key) {
            if (is_string($normalizedMetadata[$key] ?? null)) {
                $normalizedMetadata[$key] = trim($normalizedMetadata[$key]);
            }
        }

        if (is_string($normalizedMetadata['platform'] ?? null)) {
            $normalizedMetadata['platform'] = mb_strtolower(trim($normalizedMetadata['platform']));
        }

        $this->merge([
            'metadata' => $normalizedMetadata,
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $metadata = $this->input('metadata');

            if (! is_array($metadata)) {
                return;
            }

            $allowedKeys = [
                'device_id',
                'app_version',
                'platform',
            ];

            $unexpectedKeys = array_diff(array_keys($metadata), $allowedKeys);

            if ($unexpectedKeys !== []) {
                $validator->errors()->add('metadata', 'The metadata field must not have any additional fields.');
            }
        });
    }
}

<?php

namespace App\Http\Requests;

use App\Http\Payloads\Game\SubmitScorePayload;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SubmitScoreRequest extends ApiFormRequest
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
            'score' => ['required', 'integer', 'min:0'],
            'coins_collected' => ['required', 'integer', 'min:0'],
            'metadata' => ['nullable', 'array'],
            'metadata.duration' => ['nullable', 'integer'],
            'metadata.app_version' => ['nullable', 'string', 'max:32'],
            'metadata.device_id' => ['nullable', 'string', 'max:191'],
            'metadata.platform' => ['nullable', 'string', Rule::in(['ios', 'android'])],
        ];
    }

    public function payload(): SubmitScorePayload
    {
        return new SubmitScorePayload(
            sessionToken: $this->string('session_token')->toString(),
            score: $this->integer('score'),
            coinsCollected: $this->integer('coins_collected'),
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
                'duration',
                'app_version',
                'device_id',
                'platform',
            ];

            $unexpectedKeys = array_diff(array_keys($metadata), $allowedKeys);

            if ($unexpectedKeys !== []) {
                $validator->errors()->add('metadata', 'The metadata field must not have any additional fields.');
            }
        });
    }
}

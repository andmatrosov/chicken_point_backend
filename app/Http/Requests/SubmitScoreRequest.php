<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

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
            'metadata.app_version' => ['nullable', 'string', 'max:32'],
            'metadata.device_id' => ['nullable', 'string', 'max:191'],
        ];
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
            ];

            $unexpectedKeys = array_diff(array_keys($metadata), $allowedKeys);

            if ($unexpectedKeys !== []) {
                $validator->errors()->add('metadata', 'The metadata field must not have any additional fields.');
            }
        });
    }
}

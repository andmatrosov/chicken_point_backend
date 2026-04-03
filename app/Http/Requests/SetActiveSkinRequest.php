<?php

namespace App\Http\Requests;

class SetActiveSkinRequest extends ApiFormRequest
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
            'skin_id' => ['required', 'integer', 'exists:skins,id'],
        ];
    }
}

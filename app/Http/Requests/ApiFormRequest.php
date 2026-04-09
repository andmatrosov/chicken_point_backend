<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class ApiFormRequest extends FormRequest
{
    protected function normalizeStringInput(string $key): mixed
    {
        if (! $this->exists($key)) {
            return $this->input($key);
        }

        return is_string($this->input($key))
            ? trim((string) $this->input($key))
            : $this->input($key);
    }

    protected function normalizeLowercaseStringInput(string $key): mixed
    {
        $value = $this->normalizeStringInput($key);

        return is_string($value) ? mb_strtolower($value) : $value;
    }
}

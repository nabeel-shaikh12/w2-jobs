<?php

namespace App\Casts;

use App\Traits\Uploader;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

class FileCaster implements CastsAttributes
{
    use Uploader;

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if (is_file($value)) {
            return $this->uploadFile($value);
        } else {
            return $value ?? $attributes[$key];
        }
    }
}

<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Google2FA\Google2FA;

class Email2FARule implements Rule
{
    private $cacheKey;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        return Cache::get($this->cacheKey) == $value;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return '2FA secret code is invalid';
    }
}

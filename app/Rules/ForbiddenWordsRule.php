<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ForbiddenWordsRule implements Rule
{
    private $forbiddenWords;
    private $forbiddenWord;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($forbiddenWords)
    {
        $this->forbiddenWords = $forbiddenWords;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (!empty($this->forbiddenWords)){
            foreach ((array) $this->forbiddenWords as $word)
            {
                if (stripos($value, $word) !== false) {
                    $this->forbiddenWord = $word;
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must not contain forbidden word ('.$this->forbiddenWord.').';
    }
}

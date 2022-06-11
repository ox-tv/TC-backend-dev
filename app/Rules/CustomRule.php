<?php

namespace App\Rules;

class CustomRule
{
    public static function uniqueTrimmed($punctuationMarks, $table, $column = null): uniqueTrimmedRule
    {
        return new uniqueTrimmedRule($punctuationMarks, $table, $column);
    }

    public static function forbiddenWords($forbiddenWords): ForbiddenWordsRule
    {
        return new ForbiddenWordsRule($forbiddenWords);
    }

    public static function google2FA($secret): Google2FARule
    {
        return new Google2FARule($secret);
    }

    public static function email2FA($cacheKey): Email2FARule
    {
        return new Email2FARule($cacheKey);
    }
}

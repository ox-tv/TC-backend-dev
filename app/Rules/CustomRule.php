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
}

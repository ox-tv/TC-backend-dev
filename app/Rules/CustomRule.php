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

    public static function isEthereumWalletAddress(): IsEthereumWalletAddress
    {
        return new IsEthereumWalletAddress();
    }

    public static function alphaSpace(): AlphaSpace
    {
        return new AlphaSpace();
    }
}

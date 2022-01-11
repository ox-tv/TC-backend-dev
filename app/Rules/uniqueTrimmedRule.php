<?php

namespace App\Rules;

use App\Models\User;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class uniqueTrimmedRule implements Rule
{
    private $punctuationMarks;
    private $table;
    private $column;
    private $ignore;
    private $idColumn = 'id';

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($punctuationMarks, $table, $column = null)
    {
        $this->punctuationMarks = $punctuationMarks;
        $this->table = $table;
        $this->column = $column;
    }

    public function ignore($id, $idColumn = null)
    {
        $this->ignore = $id;
        $this->idColumn = $idColumn ?? 'id';

        return $this;
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
        $trimmedValue = str_replace($this->punctuationMarks,'', $value);
        $regex = implode(']|[',$this->punctuationMarks);
        $column = $this->column ?? $attribute;

        $exists = DB::table($this->table)
            ->whereRaw("REGEXP_REPLACE(?, '[?]', '') = '?'", [$column, $regex, $trimmedValue])
            ->when($this->ignore, function ($query, $ignoreId) {
                return $query->where($this->idColumn, '<>', $ignoreId);
            })->exists();

        if ($exists){
            return false;
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
        return 'The :attribute is already taken.';
    }
}

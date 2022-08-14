<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MailList extends Model
{
    protected $fillable = ['email', 'location'];

    protected $table = 'mail_list';

    // Scope
    public function scopeLocation($query, $location){
        $query->where('location', $location);
        return $query;
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class quiz_image extends Model
{
    protected $fillable = [];

    public function quiz(){
        return $this->belongsTo(quiz::class);
    }
}

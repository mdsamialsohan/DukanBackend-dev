<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tcb extends Model
{
    // Specify custom table name if it doesn't follow Laravel's pluralization convention
    protected $table = 'tcb';

    // Columns that can be mass-assigned
    protected $fillable = [
        'nid',
        'fcn',
        'word',
    ];
}

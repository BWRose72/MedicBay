<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Specialisation extends Model
{
    protected $table = 'specialisations';

    protected $primaryKey = 'specialisation_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'name',
    ];
}

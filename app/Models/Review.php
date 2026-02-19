<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{

    protected $table = 'reviews';

    protected $primaryKey = 'review_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'attitude',
        'professionalism',
    ];

    //Relationships
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
    
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }
}

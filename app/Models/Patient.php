<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patients';

    protected $primaryKey = 'patient_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'gender',
        'personal_identification_number',
        'date_of_birth',
        'phone',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Patient $patient) {
            if ($patient->isForceDeleting()) {
                return;
            }

            Appointment::query()
                ->where('patient_id', (int) $patient->getKey())
                ->where('start_time', '>', CarbonImmutable::now((string) config('app.timezone', 'Europe/Sofia'))->subMinutes(30))
                ->delete();
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_id');
    }

    // Accessors
    public function getAgeAttribute(): ?int
    {
        return $this->date_of_birth
            ? $this->date_of_birth->age
            : null;
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }
}

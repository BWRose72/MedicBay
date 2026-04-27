<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doctor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'doctors';

    protected $primaryKey = 'doctor_id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'specialisation_id',
        'phone',
        'bio',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Doctor $doctor) {
            if ($doctor->isForceDeleting()) {
                return;
            }

            Appointment::query()
                ->where('doctor_id', (int) $doctor->getKey())
                ->where('start_time', '>', CarbonImmutable::now((string) config('app.timezone', 'Europe/Sofia'))->subMinutes(30))
                ->delete();
        });
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function specialisation(): BelongsTo
    {
        return $this->belongsTo(Specialisation::class, 'specialisation_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    // Accessor
    public function getDisplayNameAttribute(): string
    {
        return 'Dr. '.($this->name ?? 'Doctor');
    }

    public function getNameAttribute(): ?string
    {
        return $this->user?->name;
    }
}

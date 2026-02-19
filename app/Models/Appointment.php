<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use App\Enums\AppointmentStatus;
use DomainException;

class Appointment extends Model
{
    use HasFactory;

    protected $table = 'appointments';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'doctor_id',
        'patient_id',
        'start_time',
        'has_left_review',
        'status',
    ];


    protected $casts = [
        'start_time' => 'datetime',
        'status' => AppointmentStatus::class,
    ];
    
    //Relationsips
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class, 'doctor_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
    
    //Accessors
    public function getEndsAtAttribute(): Carbon
    {
        return $this->start_time->copy()->addMinutes(30);
    }

    public function getIsPastAttribute(): bool
    {
        return $this->start_time->isPast();
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->start_time->isFuture();
    }

    //scopes
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_time', '>', now());
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_time', '<=', now());
    }

    public function scopeForDoctor(Builder $query, int $doctorId): Builder
    {
        return $query->where('doctor_id', $doctorId);
    }

    public function scopeForPatient(Builder $query, int $patientId): Builder
    {
        return $query->where('patient_id', $patientId);
    }

    public function transitionTo(AppointmentStatus $to): void
    {
        $from = $this->status;
        if ($from != AppointmentStatus::Scheduled) {
            throw new DomainException("Invalid appointment status transition: {$from} -> {$to->value}");
        }

        $this->status = $to;
    }
}

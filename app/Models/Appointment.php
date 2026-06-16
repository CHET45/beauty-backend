<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = [
        'service_id',
        'customer_name',
        'customer_phone',
        'starts_at',
        'ends_at',
        'status',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
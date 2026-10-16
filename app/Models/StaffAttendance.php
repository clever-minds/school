<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DateFormatTrait;
use Illuminate\Support\Facades\Auth;

class StaffAttendance extends Model
{
    use HasFactory, DateFormatTrait;

    protected $fillable = [
        'user_id',
        'school_id',
        'date',
        'check_in',
        'check_out',
        'check_in_location',
        'check_out_location',
        'latitude',
        'longitude',
        'check_out_latitude',
        'check_out_longitude',
        'check_in_ip',
        'check_out_ip',
        'status',
        'type',
        'scanned_by'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function scopeOwner($query)
    {
        if (Auth::user() && Auth::user()->school_id) {
            return $query->where('school_id', Auth::user()->school_id);
        }
        return $query;
    }

    /**
     * Calculate attendance status based on check_in and check_out working hours
     *
     * Rules:
     * - >= 7 hours (>= 420 mins) -> 1 (Present / Full Day)
     * - >= 2 hours and < 7 hours (120 to 419 mins) -> 3 (Half Day)
     * - < 2 hours (< 120 mins) -> 4 (Absent)
     * - If check_out is missing -> 3 (Half Day)
     */
    public static function calculateStatusOnCheckOut($checkIn, $checkOut)
    {
        if (empty($checkIn) || empty($checkOut)) {
            return 3; // Missing punch-out is counted as Half Day
        }

        $start = \Carbon\Carbon::parse($checkIn);
        $end = \Carbon\Carbon::parse($checkOut);
        $minutes = $start->diffInMinutes($end);

        if ($minutes >= 420) {
            return 1; // 7 or more hours -> Full Day (Present)
        } elseif ($minutes >= 120) {
            return 3; // 2 to less than 7 hours -> Half Day
        } else {
            return 4; // Less than 2 hours -> Absent
        }
    }
}

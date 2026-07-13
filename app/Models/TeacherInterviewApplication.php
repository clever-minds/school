<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherInterviewApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'resume_path',
        'status',
        'remarks'
    ];

    public function interview()
    {
        return $this->hasOne(TeacherInterview::class, 'application_id');
    }
}

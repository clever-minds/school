<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherInterviewFeedbackQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'feedback_question',
        'category',
        'status'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use App\Traits\DateFormatTrait;
use App\Traits\LogsActivity;

class StaffPerformanceEvaluation extends Model
{
    use HasFactory, DateFormatTrait, LogsActivity;

    protected $fillable = [
        'school_id',
        'staff_id',
        'month',
        'year',
        'criteria',
        'positive_points',
        'negative_points',
        'total_points',
        'score_percentage',
        'evaluated_by',
        'remarks'
    ];

    protected $casts = [
        'criteria' => 'array',
        'positive_points' => 'integer',
        'negative_points' => 'integer',
        'total_points' => 'integer',
        'score_percentage' => 'float',
    ];

    public function scopeOwner($query)
    {
        if (Auth::user() && Auth::user()->school_id) {
            return $query->where('school_id', Auth::user()->school_id);
        }
        return $query;
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluated_by');
    }

    /**
     * 10 Standard Criteria for Performance Evaluation
     */
    public static function getDefaultCriteria(): array
    {
        return [
            'punctuality' => 'Punctuality',
            'teaching_efficiency' => 'Efficiency in teaching',
            'classroom_management' => 'Classroom management',
            'attitude_towards_learners' => 'Attitude towards learners',
            'observance_of_lesson_plans' => 'Observance of lesson plans',
            'attention_to_individual_students' => 'Attention of individual students',
            'cooperation_with_management' => 'Cooperation with the management',
            'homework_diary_remarks' => 'Giving remark in homework dairy of students',
            'dedication_to_profession' => 'Dedication to the profession',
            'helping_nature' => 'Helping nature',
        ];
    }
}

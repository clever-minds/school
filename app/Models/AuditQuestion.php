<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'category', 'type', 'audit_option_group_id', 'status'];

    public function optionGroup()
    {
        return $this->belongsTo(AuditOptionGroup::class, 'audit_option_group_id');
    }

    public function answers()
    {
        return $this->hasMany(SchoolAuditAnswer::class);
    }
}

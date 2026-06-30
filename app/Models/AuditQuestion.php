<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditQuestion extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'status'];

    public function answers()
    {
        return $this->hasMany(SchoolAuditAnswer::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManageExpense extends Model
{
    use HasFactory;

    // Table name
    protected $table = 'manage_expenses';

    // Primary key

    // Mass assignable columns
    protected $fillable = [
        'title',
        'description',
        'amount',
        'balance',
        'type',
        'transaction_date',
    ];

    // Dates casting (optional)
    protected $casts = [
        'transaction_date' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // If you don't use auto-incrementing 'exp_id', set this
    public $incrementing = true; // set to false if 'exp_id' is not auto-increment

    // If your primary key is not integer
    protected $keyType = 'int'; // change to 'string' if exp_id is string
}

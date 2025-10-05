<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstructorEmployeeAssignment extends Model
{
    use HasFactory;

    protected $table = 'instructor_employee_assignments';
    protected $fillable = ['instructor_id','employee_id'];
}

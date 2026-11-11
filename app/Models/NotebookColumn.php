<?php

namespace App\Models;

use App\Models\Concerns\BelongsToAcademicYear;
use Illuminate\Database\Eloquent\Model;

/** Cantidad de casillas de una dimensión del cuaderno (ver App\Services\NotebookColumns). */
class NotebookColumn extends Model
{
    use BelongsToAcademicYear;

    protected $fillable = ['academic_year_id', 'subject_id', 'parallel_id', 'quarter', 'dimension', 'count', 'user_id'];

    protected $casts = ['quarter' => 'integer', 'count' => 'integer'];
}

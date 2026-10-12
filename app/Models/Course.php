<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    use HasFactory;

    public const NIVEL_PRIMARIA = 'primaria_comunitaria_vocacional';
    public const NIVEL_SECUNDARIA = 'secundaria_comunitaria_productiva';

    public const NIVELES = [
        self::NIVEL_PRIMARIA => 'Primaria Comunitaria Vocacional',
        self::NIVEL_SECUNDARIA => 'Secundaria Comunitaria Productiva',
    ];

    protected $fillable = [
        'name',
        'nivel_educativo',
    ];

    public function nivelLabel(): string
    {
        return self::NIVELES[$this->nivel_educativo] ?? 'Sin nivel asignado';
    }

    public function parallels(): HasMany
    {
        return $this->hasMany(Parallel::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
}

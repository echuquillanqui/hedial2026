<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MedicationCatalog extends Model
{
    protected $table = 'medication_catalog';

    protected $fillable = ['code', 'name', 'reference_quantity', 'frequency'];

    protected $casts = ['reference_quantity' => 'integer'];
}

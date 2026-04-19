<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class PracticalApparatusItem extends Model
{
    protected $fillable = [
        'practical_apparatus_list_id',
        'item_name',
        'quantity',
        'unit',
        'remarks',
        'display_order',
    ];

    public function apparatusList()
    {
        return $this->belongsTo(PracticalApparatusList::class, 'practical_apparatus_list_id');
    }
}

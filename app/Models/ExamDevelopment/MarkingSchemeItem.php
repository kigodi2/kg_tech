<?php

namespace App\Models\ExamDevelopment;

use Illuminate\Database\Eloquent\Model;

class MarkingSchemeItem extends Model
{
    protected $fillable = [
        'marking_scheme_id',
        'item_label',
        'description',
        'marks',
        'display_order',
    ];

    public function scheme()
    {
        return $this->belongsTo(MarkingScheme::class, 'marking_scheme_id');
    }
}

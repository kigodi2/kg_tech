<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportExport extends Model
{
    use HasFactory;

    protected $table = 'report_exports';

    protected $fillable = [
        'user_id', 'exam_year_id', 'scope', 'export_type', 'action',
        'parameters', 'status', 'file_path', 'file_size',
        'ip_address', 'user_agent', 'error_message',
    ];

    protected $casts = [
        'parameters' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('export_type', $type);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public static function log(string $exportType, string $action, array $params = [], string $scope = 'school'): self
    {
        return static::create([
            'user_id' => auth()->id(),
            'exam_year_id' => $params['exam_year_id'] ?? null,
            'scope' => $scope,
            'export_type' => $exportType,
            'action' => $action,
            'parameters' => $params,
            'status' => 'completed',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}

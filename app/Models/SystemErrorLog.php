<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class SystemErrorLog extends Model
{
    use HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'level',
        'source',
        'message',
        'context',
        'user_id',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

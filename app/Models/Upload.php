<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = [
        'original_name',
        'storage_path',
        'checksum',
        'status',
        'total_rows',
        'processed_rows',
        'failed_rows',
        'error_message',
        'completed_at',
    ];
    
    protected $casts = [
        'completed_at' => 'datetime',
    ];
}

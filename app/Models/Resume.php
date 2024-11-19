<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model; // Make sure this is correctly imported

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
        'meta',
        'is_star',
        'status',
        'address',
        'phone',
        'category_id',
        'provider',
        'provider_id',
        'plan',
        'plan_id',
        'credits',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}

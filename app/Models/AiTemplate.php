<?php

namespace App\Models;

use App\Casts\FileCaster;
use App\Traits\UUID;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AiTemplate extends Model
{
    use HasFactory, UUID;

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $casts = [
        'fields' => 'array',
        'icon' => FileCaster::class,
        'preview' => FileCaster::class,
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}

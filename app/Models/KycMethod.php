<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KycMethod extends Model
{
    protected $fillable = [
        "title",
        "image",
        "image_accept",
        "status",
        "fields",
    ];

    protected $casts = [
        'fields' => 'json'
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['created_at_date'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('kyc_request_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(KYCRequest::class);
    }

    public function getCreatedAtDateAttribute()
    {
        return $this->created_at?->format('d m, Y h:i A');
    }
}

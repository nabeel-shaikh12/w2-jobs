<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Conversation extends Model
{
    use \Staudenmeir\EloquentEagerLimit\HasEagerLimit;

    use HasFactory;
    protected $guarded = [];
    protected static function boot()
    {
        parent::boot();
        // assign the uuid
        static::creating(function ($conversation) {
            $conversation->uuid = str()->uuid();
        });
    }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
    public function receiver(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->whereNot('user_id', auth()->id());
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }
    public function lastReply()
    {
        return $this->hasMany(Reply::class)->orderBy('created_at', 'desc')->limit(1);
    }

    public function deleted_by()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id');
    }
}

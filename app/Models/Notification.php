<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['created_at_human_date'];

    /**
     * Get the 
     *
     * @param  string  $value
     * @return string
     */
    public function getCreatedAtHumanDateAttribute($value)
    {
        return $this->created_at->diffForHumans();
    }
}

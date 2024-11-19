<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'status',
        'type',
        'is_featured',
        'category_id',
        'is_menu_item',
        'lang',
        'icon',
        'preview',
    ];

    //
    public function categories()
    {
        return $this->hasMany(Category::class, 'category_id', 'id');
    }

    //parent category
    public function parent()
    {
        return $this->hasOne(Category::class, 'id', 'category_id');
    }

    //nasted
    public function childrenCategories()
    {
        return $this->hasMany(Category::class, 'category_id', 'id')->with('categories');
    }

    public function postcategory()
    {
        return $this->belongsTo(Postcategory::class);
    }

    public function postcategories()
    {
        return $this->hasMany(Postcategory::class);
    }

    public function posts()
    {
        return $this->belongsToMany('App\Models\Post', 'postcategories', 'post_id', 'category_id');
    }

    public function companies()
    {
        return $this->hasMany(User::class, 'category_id', 'id');
    }

    public function scopeActive($query): Builder
    {
        return $query->where('status', 1);
    }

    public function jobs()
    {
        return $this->belongsToMany(Opening::class, 'category_opening', 'category_id', 'opening_id');
    }
}

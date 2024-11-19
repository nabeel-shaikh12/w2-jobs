<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
	use HasFactory;

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = ['name', 'slug', 'location_id', 'meta'];

	/**
	 * The attributes that should be cast to native types.
	 *
	 * @var array
	 */
	protected $casts = [
		'meta' => 'json',
	];

	/**
	 * Get the route key for the model.
	 *
	 * @return string
	 */
	public function getRouteKeyName(): string
	{
		return 'slug';
	}

	public function locations()
	{
		return $this->hasMany(Location::class, 'location_id', 'id');
	}

	//parent category
	public function parent()
	{
		return $this->hasOne(Location::class, 'id', 'location_id');
	}

	//nasted
	public function childrenLocation()
	{
		return $this->hasMany(Location::class, 'location_id', 'id')->with('locations');
	}
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Route extends Model
{
    const STATUS_ACTIVE = 'active';
    const STATUS_CLOSED = 'closed';
    const STATUS_BAN = 'ban';

    protected $table = 'routes';
    protected $primaryKey = 'route_id';
    protected $guarded = ['route_id'];
    public $timestamps = false;
    protected $casts = [
        'route_points' => 'object',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->user_id = request()->user()->user_id;
            $model->route_parent = 0;
            $model->route_type = 'route';
            $model->route_status = self::STATUS_ACTIVE;
            $model->route_look = 0;
            $model->route_register_date = date('Y-m-d');
        });
    }

    public function setRouteFromCityAttribute($value)
    {
        $this->attributes['route_from_city'] = is_null($value) ? 0 : $value;
    }

    public function setRouteToCityAttribute($value)
    {
        $this->attributes['route_to_city'] = is_null($value) ? 0 : $value;
    }

    public function getRoutePointsAttribute($json)
    {
        if (is_array($json)) return $json;

        return json_decode($json, true);
    }

}

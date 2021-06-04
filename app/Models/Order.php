<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $primaryKey = 'order_id';
    protected $guarded = ['order_id'];
    public $timestamps = false;

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->order_register_date = $model->freshTimestamp();
        });

        static::saving(function ($model) {
            $model->order_url = Str::slug($model->order_name . ' ' . $model->order_id);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

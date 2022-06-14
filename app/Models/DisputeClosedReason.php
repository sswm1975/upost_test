<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\DisputeClosedReason
 *
 * @property int $id Код
 * @property string $name Наименование
 * @property string $guilty Виновен: Исполнитель или Заказчик
 * @property string $alias Алиас
 * @property-read string $alias_name
 * @method static Builder|DisputeClosedReason newModelQuery()
 * @method static Builder|DisputeClosedReason newQuery()
 * @method static Builder|DisputeClosedReason query()
 * @method static Builder|DisputeClosedReason quickSearch($search)
 * @method static Builder|DisputeClosedReason whereAlias($value)
 * @method static Builder|DisputeClosedReason whereGuilty($value)
 * @method static Builder|DisputeClosedReason whereId($value)
 * @method static Builder|DisputeClosedReason whereName($value)
 * @mixin \Eloquent
 */
class DisputeClosedReason extends Model
{
    protected $table = 'dispute_closed_reasons';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'alias'];
    protected $appends = ['alias_name'];
    public $timestamps = false;

    ### GETTERS ###

    public function getAliasNameAttribute(): string
    {
        return system_message($this->alias);
    }

    ### SCOPES ###

    public function scopeQuickSearch($query, $search)
    {
        return $query->where(function($query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('alias', 'like', "%{$search}%");
        });
    }
}

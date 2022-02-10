<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\Message
 *
 * @property int $id Код
 * @property int $chat_id Код чата
 * @property int $user_id Автор сообщения
 * @property string $text Текст сообщения
 * @property array|null $files Прикрепленные файлы
 * @property string|null $created_at Добавлено
 * @property string|null $updated_at Изменено
 * @property-read \App\Models\Chat $chat
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereFiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUserId($value)
 * @mixin \Eloquent
 */
class Message extends Model
{
    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = [
        'files' => 'array',
    ];

    ### BOOT ###

    /**
     * Boot model.
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_at = $model->freshTimestamp();
        });

        static::updating(function ($model) {
            $model->updated_at = $model->freshTimestamp();
        });
    }

    ### SETTERS ###

    public function setTextAttribute($value)
    {
        $this->attributes['text'] = strip_tags(strip_unsafe($value), ['p', 'span', 'b', 'i', 's', 'u', 'strong', 'italic', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6']);
    }

    public function setFilesAttribute($files)
    {
        if (empty($files)) {
            $this->attributes['files'] = null;
        }

        foreach ($files as $key => $file) {
            $uri_parts = explode('/', $file);
            $files[$key] = end($uri_parts);
        }

        $this->attributes['files'] = json_encode($files);
    }

    ### RELATIONS ###

    /**
     * Чат.
     *
     * @return BelongsTo
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Автор сообщения.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

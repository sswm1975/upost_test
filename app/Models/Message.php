<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Traits\TimestampSerializable;

/**
 * App\Models\Message
 *
 * @property int $id Код
 * @property int $chat_id Код чата
 * @property int $user_id Автор сообщения
 * @property int|null $dispute_id Код спора
 * @property string $text
 * @property array $images Фотографии
 * @property \Illuminate\Support\Carbon|null $created_at Добавлено
 * @property \Illuminate\Support\Carbon|null $updated_at Изменено
 * @property-read \App\Models\Chat $chat
 * @property-read array $images_original
 * @property-read array $images_thumb
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|Message newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Message query()
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereChatId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereDisputeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereImages($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Message whereUserId($value)
 * @mixin \Eloquent
 */
class Message extends Model
{
    use TimestampSerializable;

    public $timestamps = false;
    protected $guarded = ['id'];
    protected $casts = [
        'images' => 'array',
    ];
    protected $dates = [
	    'created_at',
        'updated_at',
    ];
    protected $appends = [
        'images_thumb',
        'images_original',
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

    /**
     * Если пользователя нет (NULL), то это системный пользователь, возвращаем 0.
     *
     * @param $user_id
     * @return int
     */
    public function getUserIdAttribute($user_id): int
    {
        return $user_id ?: 0;
    }

    /**
     * Для системных сообщений в таблице хранится алиас, а клиенту отдаем текст в локали пользователя.
     *
     * @param $text
     * @return string
     */
    public function getTextAttribute($text): string
    {
        $dispute_id = $this->dispute_id ?: 0;
        return $this->user_id == SYSTEM_USER_ID || $dispute_id ? system_message($text, $dispute_id) : $text;
    }

    public function getImagesAttribute($images): array
    {
        if (empty($images)) return [];

        $type = empty($this->dispute_id) ? 'chats' : 'disputes';

        $link_images = [];
        foreach (json_decode($images) as $image) {
            $link_images[] = asset("storage/{$this->user_id}/{$type}/{$image}");
        }

        return $link_images;
    }

    public function getImagesThumbAttribute(): array
    {
        if (is_null($this->images)) return [];

        $images = [];
        foreach ($this->images as $image) {
            $images[] = str_replace('image_', 'image_thumb_', $image);
        }

        return $images;
    }

    public function getImagesOriginalAttribute(): array
    {
        if (is_null($this->images)) return [];

        $images = [];
        foreach ($this->images as $image) {
            $images[] = str_replace('image_', 'image_original_', $image);
        }

        return $images;
    }

    ### SETTERS ###

    public function setTextAttribute($value)
    {
        $this->attributes['text'] = strip_tags(strip_unsafe($value), ['p', 'span', 'b', 'i', 'u', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'br']);
    }

    public function setImagesAttribute($images)
    {
        if (empty($images)) {
            $this->attributes['images'] = null;
        }

        foreach ($images as $key => $file) {
            $uri_parts = explode('/', $file);
            $images[$key] = end($uri_parts);
        }

        $this->attributes['images'] = json_encode($images);
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

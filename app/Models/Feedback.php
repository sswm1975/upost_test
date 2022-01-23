<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;

class Feedback extends Model
{
    use TimestampSerializable;

    protected $table = 'feedback';
    protected $guarded = ['id'];

    public const SUBJECT_WITHOUT_NAME = 'without_name';
    public const SUBJECT_HAVE_QUESTION = 'have_question';

    public const SUBJECT_TYPES = [
        self::SUBJECT_WITHOUT_NAME  => 'Без темы',
        self::SUBJECT_HAVE_QUESTION => 'У Вас есть Вопрос?',
    ];

    ### GETTERS ###

    public function getSubjectAttribute($subject)
    {
        if (empty($subject)) {
            $subject = static::SUBJECT_WITHOUT_NAME;
        }

        return Arr::get(static::SUBJECT_TYPES, $subject, static::SUBJECT_TYPES[static::SUBJECT_WITHOUT_NAME]);
    }

    ### LINKS ###

    public function read_admin_user(): BelongsTo
    {
        $userModel = config('admin.database.users_model');

        return $this->belongsTo($userModel, 'read_admin_user_id');
    }
}

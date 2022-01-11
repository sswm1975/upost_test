<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class Feedback extends Model
{
    protected $table = 'feedback';
    protected $guarded = ['id'];

    public const SUBJECT_WITHOUT_NAME = 'without_name';
    public const SUBJECT_HAVE_QUESTION = 'have_question';

    public const SUBJECT_TYPES = [
        self::SUBJECT_WITHOUT_NAME  => 'Без темы',
        self::SUBJECT_HAVE_QUESTION => 'У Вас есть Вопрос?',
    ];

    public function getSubjectAttribute($subject)
    {
        if (empty($subject)) {
            $subject = static::SUBJECT_WITHOUT_NAME;
        }

        return Arr::get(static::SUBJECT_TYPES, $subject, static::SUBJECT_TYPES[static::SUBJECT_WITHOUT_NAME]);
    }
}

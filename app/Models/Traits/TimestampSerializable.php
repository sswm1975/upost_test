<?php

namespace App\Models\Traits;

use DateTimeInterface;

trait TimestampSerializable
{
    /**
     * Prepare a date for array / JSON serialization.
     * https://laravel.com/docs/7.x/upgrade#date-serialization
     *
     * @param  DateTimeInterface  $date
     * @return string
     */
    protected function serializeDate(DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}

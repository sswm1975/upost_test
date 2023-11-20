<?php

namespace App\Models;

use App\Models\Traits\TimestampSerializable;
use Illuminate\Database\Eloquent\Model;

class UsSalesTax extends Model
{
    use TimestampSerializable;
    protected $table = 'us_sales_tax';
    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
}

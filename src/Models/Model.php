<?php

namespace XuanChen\UnionPay\Models;

use Carbon\Carbon;

class Model extends \Illuminate\Database\Eloquent\Model
{

    protected $guarded = [];

    protected function serializeDate(\DateTimeInterface $date)
    {
        if (version_compare(app()->version(), '7.0.0') < 0) {
            return parent::serializeDate($date);
        }

        return $date->format(Carbon::DEFAULT_TO_STRING_FORMAT);
    }

}

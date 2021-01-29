<?php

namespace XuanChen\UnionPay\Models;

class UnionpayCheckLog extends Model
{

    public $casts = [
        'source' => 'json',
    ];

    public function check()
    {
        return $this->hasOne(UnionpayCheck::class);
    }

}

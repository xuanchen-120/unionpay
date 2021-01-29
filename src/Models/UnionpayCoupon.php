<?php

namespace XuanChen\UnionPay\Models;

class UnionpayCoupon extends Model
{

    const STATUS_INIT = 1;
    const STATUS_USED = 2;
    const STATUS      = [
        self::STATUS_INIT => '未使用',
        self::STATUS_USED => '已使用',
    ];

    public function log()
    {
        return $this->belongsTo(UnionpayLog::class);
    }

}

<?php

namespace XuanChen\UnionPay\Models;

use App\Models\Coupon;

class UnionpayLog extends Model
{

    protected $casts = [
        'in_source'  => 'array',
        'out_source' => 'array',
    ];

    const STATUS_SUCCESS = 1;
    const STATUS_ERROR   = 0;
    const STATUS         = [
        self::STATUS_SUCCESS => '成功',
        self::STATUS_ERROR   => '失败',
    ];

    public function coupon()
    {
        return $this->hasOne(UnionpayCoupon::class, 'redemptionCode', 'mkt_code');
    }

}

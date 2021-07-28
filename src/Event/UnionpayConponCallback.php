<?php

namespace XuanChen\UnionPay\Event;

use XuanChen\UnionPay\Models\UnionpayCoupon;

/**
 * 核券之后的回调
 */
class UnionpayConponCallback
{

    public $coupon;

    public function __construct(UnionpayCoupon $coupon)
    {
        $this->coupon = $coupon;
    }

}

<?php

namespace XuanChen\UnionPay\Models;

use App\Models\User;

class UnionpayCheckLog extends Model
{

    public $casts = [
        'source' => 'json',
    ];

    const STATUS_INIT   = 1;
    const STATUS_REPEAL = 2;
    const STATUS        = [
        self::STATUS_INIT   => '正常',
        self::STATUS_REPEAL => '撤销',
    ];

    public function check()
    {
        return $this->hasOne(UnionpayCheck::class);
    }

    public function outlet()
    {
        return $this->hasOne(User::class, 'shop_id', 'shop');
    }

    public function unionlog()
    {
        return $this->hasOne(UnionpayLog::class, 'req_serial_no', 'req_serial_no');
    }

}

<?php

namespace XuanChen\UnionPay\Models;

use App\Models\User;

class UnionpayCoupon extends Model
{

    const STATUS_INIT = 1;
    const STATUS_USED = 2;
    const STATUS      = [
        self::STATUS_INIT => '未使用',
        self::STATUS_USED => '已使用',
    ];

    public function getStatusTextAttribute()
    {
        return self::STATUS[$this->status] ?? '未知';
    }

    public function log()
    {
        return $this->belongsTo(UnionpayLog::class);
    }

    /**
     * Notes: 关联网点
     * @Author: 玄尘
     * @Date  : 2021/8/31 15:42
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function outlet()
    {
        return $this->hasOne(User::class, 'shop_id', 'shop_no');
    }

}

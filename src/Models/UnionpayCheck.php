<?php

namespace XuanChen\UnionPay\Models;

class UnionpayCheck extends Model
{

    public function logs()
    {
        return $this->hasMany(UnionpayCheckLog::class);
    }

}

<?php

namespace XuanChen\UnionPay\Action;

use App\Models\ActivityCoupon;
use XuanChen\UnionPay\Models\UnionpayLog;
use App\Models\User;
use XuanChen\Coupon\Coupon;
use XuanChen\UnionPay\Contracts\Contracts;
use XuanChen\UnionPay\UnionPay;

class Redemption implements Contracts
{

    /**
     * Parent unionpay.
     * @var UnionPay
     */
    protected $unionpay;

    public function __construct(UnionPay &$unionpay)
    {
        $this->unionpay = $unionpay;
    }

    public function start()
    {
        //查询聚合信息
        $query = UnionpayLog::where('req_serial_no', $this->unionpay->params['orig_req_serial_no'])
                            ->where('msg_txn_code', '002025')
                            ->latest()
                            ->first();
        if (!$query) {
            $this->unionpay->outdata['msg_rsp_code'] = '9999';
            $this->unionpay->outdata['msg_rsp_desc'] = '销账失败，未查询到前置数据。';
        } else {
            $this->unionpay->outdata['orig_amt']     = (int)$query->in_source['amount'];              //订单金额 原始金额
            $this->unionpay->outdata['discount_amt'] = $query->out_source['discount'];                //折扣金额
            $this->unionpay->outdata['pay_amt']      = $query->out_source['actual_amt'];              //折扣后金额

            //获取银联渠道
            $user = User::find($this->unionpay->agent_id);

            $coupon = Coupon::Redemption(
                $user,
                $query->mkt_code,
                $this->unionpay->params['orig_amt'] / 100,
                $this->unionpay->outlet_id,
                ''
            );

            if (!is_array($coupon)) {
                $this->unionpay->outdata['msg_rsp_code'] = '9999';
                $this->unionpay->outdata['msg_rsp_desc'] = $coupon;
            }

        }

    }

    public function back()
    {
        return $this->unionpay->outdata;
    }

}
<?php

namespace XuanChen\UnionPay\Action;

use XuanChen\Coupon\Coupon;
use XuanChen\UnionPay\Contracts\Contracts;
use XuanChen\UnionPay\UnionPay;

class Query implements Contracts
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
        try {
            $res = Coupon::Query($this->unionpay->params['mkt_code'], $this->unionpay->outlet_id);

            if (is_array($res)) {
                $this->unionpay->outdata['pos_display'] = $res['name'];
                $this->unionpay->outdata['discount']    = $res['price'] * 100;
                $this->unionpay->outdata['actual_amt']  = (int)bcsub($this->unionpay->params['amount'], $res['price'] * 100);
            } else {
                $this->unionpay->outdata['msg_rsp_code'] = '9999';
                $this->unionpay->outdata['msg_rsp_desc'] = $res;
            }
        } catch (\Exception $e) {
            $this->unionpay->outdata['msg_rsp_code'] = '9999';
            $this->unionpay->outdata['msg_rsp_desc'] = $e->getMessage();
        }

    }

    public function back()
    {
        return $this->unionpay->outdata;
    }

}

<?php

namespace XuanChen\UnionPay\Action;

use GuzzleHttp\Client;
use XuanChen\UnionPay\Models\UnionpayLog;
use XuanChen\UnionPay\Models\UnionpayCoupon;
use Carbon\Carbon;
use XuanChen\Coupon\Coupon;
use XuanChen\UnionPay\Contracts\Contracts;
use XuanChen\UnionPay\UnionPay;
use XuanChen\UnionPay\Utils\Helper;
use XuanChen\UnionPay\Event\UnionpayConponCallback;

class UpdateCode implements Contracts
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

            $params = $this->unionpay->params;
            $info   = UnionpayCoupon::where('coupon_no', $params['coupon_no'])->first();

            if (!$info) {
                $this->unionpay->outdata['msg_rsp_code'] = 3001;
                $this->unionpay->outdata['msg_rsp_desc'] = '未查询到优惠券信息';

                return;
            }

            $info->orig_amt        = $params['orig_amt'];
            $info->discount_amt    = $params['discount_amt'];
            $info->pay_amt         = $params['pay_amt'];
            $info->req_serial_no   = $params['req_serial_no'];
            $info->shop_no         = $params['shop_no'];
            $info->trans_crrltn_no = $params['trans_crrltn_no'] ?? '';
            $info->order_no        = $params['order_no'] ?? '';
            $info->status          = 2;
            $info->save();

            event(new UnionpayConponCallback($info));

        } catch (\Exception $e) {
            $this->unionpay->outdata['msg_rsp_code'] = '3001';
            $this->unionpay->outdata['msg_rsp_desc'] = $e->getMessage();
        }

    }

    public function back()
    {
        return $this->unionpay->outdata;
    }

}
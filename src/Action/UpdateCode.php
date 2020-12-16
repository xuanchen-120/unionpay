<?php

namespace XuanChen\UnionPay\Action;

use XuanChen\UnionPay\Models\UnionpayLog;
use XuanChen\UnionPay\Models\UnionpayCoupon;
use Carbon\Carbon;
use XuanChen\Coupon\Coupon;
use XuanChen\UnionPay\Contracts\Contracts;
use XuanChen\UnionPay\UnionPay;
use XuanChen\UnionPay\Utils\Helper;

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

            $info = UnionpayCoupon::where('coupon_no', $params['coupon_no'])->first();
            if (!$info) {
                $this->unionpay->outdata['msg_rsp_code'] = 9996;
                $this->unionpay->outdata['msg_rsp_desc'] = '未查询到优惠券信息';

                return;
            }
            $info->
            //成功
            if (isset($ret['msg_rsp_code']) && $ret['msg_rsp_code'] == '0000') {
                //                $app->setSign(true);
                $checksign = $this->checkSign(false, false);
                if ($checksign !== true) {
                    $this->unionpay->outdata['msg_rsp_code'] = 9996;
                    $this->unionpay->outdata['msg_rsp_desc'] = '获取优惠券数据验签失败';
                }

                if (!isset($ret['coupon_list']) || !isset($ret['coupon_no'])) {
                    $this->unionpay->outdata['msg_rsp_code'] = 9996;
                    $this->unionpay->outdata['msg_rsp_desc'] = '没有找到优惠券信息。';
                }

                $coupon = [
                    'coupon_no'           => $ret['coupon_list']['coupon_no'],
                    'effective_date_time' => $ret['coupon_list']['effective_date_time'],
                    'expire_date_time'    => $ret['coupon_list']['expire_date_time'],
                ];
            } else {
                $this->unionpay->outdata['msg_rsp_code'] = '9999';
                $this->unionpay->outdata['msg_rsp_desc'] = $ret['msg_rsp_desc'];
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
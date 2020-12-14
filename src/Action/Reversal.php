<?php

namespace XuanChen\UnionPay\Action;

use App\Models\UnionpayLog;
use XuanChen\Coupon\Coupon;
use App\Models\Coupon as CouponModel;
use XuanChen\UnionPay\Contracts\Contracts;
use XuanChen\UnionPay\UnionPay;

class Reversal implements Contracts
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
            //查询聚合信息
            $info = UnionpayLog::where('req_serial_no', $this->unionpay->params['orig_req_serial_no'])
                               ->where('msg_txn_code', '002100')
                               ->where('status', 1)
                               ->latest()
                               ->first();

            if ($info) {
                $query = UnionpayLog::where('req_serial_no', $info->orig_req_serial_no)
                                    ->where('msg_txn_code', '002025')
                                    ->where('status', 1)
                                    ->latest()
                                    ->first();

                $coupon = CouponModel::where('redemptionCode', $query->mkt_code)
                                     ->where('status', 2)
                                     ->latest()
                                     ->first();

                if ($query && $coupon) {
                    //优惠券核销成功
                    if ($coupon->status == 2) {
                        $res = Coupon::Reversal($coupon->redemptionCode, $this->unionpay->outlet_id);
                        if ($res !== true) {
                            $this->unionpay->outdata['msg_rsp_code'] = '9999';
                            $this->unionpay->outdata['msg_rsp_desc'] = $res;
                        }
                    } else {
                        $this->unionpay->outdata['msg_rsp_code'] = '9999';
                        $this->unionpay->outdata['msg_rsp_desc'] = '优惠券状态不对';
                    }
                } else {
                    $this->unionpay->outdata['msg_rsp_code'] = '9999';
                    $this->unionpay->outdata['msg_rsp_desc'] = '未查询到卡券信息。';
                }

            } else {
                $this->unionpay->outdata['msg_rsp_code'] = '9999';
                $this->unionpay->outdata['msg_rsp_desc'] = '未查询到销账接口数据。';
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
<?php

namespace XuanChen\UnionPay\Action;

use XuanChen\UnionPay\Models\UnionpayLog;
use XuanChen\UnionPay\Models\UnionpayCoupon;
use Carbon\Carbon;
use XuanChen\Coupon\Coupon;
use App\Models\Coupon as CouponModel;
use XuanChen\UnionPay\Contracts\Contracts;
use XuanChen\UnionPay\UnionPay;
use XuanChen\UnionPay\Utils\Helper;

class GetCode implements Contracts
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
            $url = config('unionpay.unionpay_url.code');

            $data = [
                'msg_type'      => '00',
                'msg_txn_code'  => '106040',
                'msg_crrltn_id' => $this->unionpay->params['msg_crrltn_id'],
                'msg_flg'       => 0,
                'msg_sender'    => config('unionpay.msg_sender'),
                'msg_time'      => Carbon::now()->format('YmdHis'),
                'msg_sys_sn'    => Helper::orderid(20),
                'msg_ver'       => '0.2',
                'sp_chnl_no'    => config('unionpay.msg_sender'),
                'sp_order_no'   => Helper::orderid(20),
                'order_date'    => Carbon::now()->format('Ymd'),
                'event_no'      => $this->unionpay->params['event_no'],
                'issue_user_id' => $this->unionpay->params['issue_user_id'],
            ];

            $app = app('xuanchen.unionpay');
            $app->setSign(false);
            //设置入参
            $app->setParams($data);
            //校验入参
            $app->checkInData();
            //入库
            $app->InputData();

            $app->params['sign'] = $app->getSign(false);

            $ret          = $app->sendPost($url, $app->params);
            $app->outdata = $ret;
            $app->updateOutData();

            if (isset($ret['code']) && $ret['code'] == 0) {
                $this->unionpay->outdata['msg_rsp_code'] = '9999';
                $this->unionpay->outdata['msg_rsp_desc'] = $ret['message'];

                return;
            }

            //成功
            if (isset($ret['msg_rsp_code']) && $ret['msg_rsp_code'] == '0000') {
                $app->setSign(true);
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
                    'mobile'              => $this->unionpay->params['mobile'],
                    'openid'              => $this->unionpay->params['openid'],
                    'event_no'            => $this->unionpay->params['event_no'],
                    'coupon_no'           => $ret['coupon_list']['coupon_no'],
                    'effective_date_time' => $ret['coupon_list']['effective_date_time'],
                    'expire_date_time'    => $ret['coupon_list']['expire_date_time'],
                ];
                
                $info = UnionpayCoupon::create($coupon);
                if (!$info) {
                    $this->unionpay->outdata['msg_rsp_code'] = '9999';
                    $this->unionpay->outdata['msg_rsp_desc'] = '券码入库失败';
                }
                $this->unionpay->outdata;
                $this->unionpay->outdata = array_merge($basics, [
                    'coupon_no'           => $info->coupon_no,
                    'effective_date_time' => $info->effective_date_time,
                    'expire_date_time'    => $info->expire_date_time,
                    'mobile'              => $info->mobile,
                ]);
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
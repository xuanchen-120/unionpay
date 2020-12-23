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
                'msg_type'      => 10,
                'msg_txn_code'  => '106040',
                'msg_crrltn_id' => $this->unionpay->params['msg_crrltn_id'],
                'msg_flg'       => '0',
                'msg_sender'    => config('unionpay.msg_sender'),
                'msg_time'      => Carbon::now()->format('YmdHis'),
                'msg_sys_sn'    => Helper::orderid(20),
                'msg_ver'       => '0.2',
                'sign_type'     => 'RSA',
                'sp_chnl_no'    => config('unionpay.msg_sender'),
                'sp_order_no'   => $this->unionpay->params['sp_order_no'],
                'order_date'    => Carbon::now()->format('Ymd'),
                'event_no'      => $this->unionpay->params['event_no'],
                'buy_quantity'  => 1,
                'issue_user_id' => $this->unionpay->params['issue_user_id'],
            ];

            $app = app('xuanchen.unionpay');
            $app->setSign(false);
            //设置入参
            $app->setParams($data);
            //校验入参
            $app->checkInData();

            $app->params['sign'] = $app->getSign(false);

            //入库
            $app->InputData();

            //申请发券
            $ret          = $app->sendPost($url, $app->params);
            $app->outdata = $ret;
            $app->sign    = $ret['sign'] ?? '';

            info(json_encode($ret));

            if (isset($ret['code']) && $ret['code'] == 0) {
                $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_code'] = '9999';
                $app->outdata['msg_rsp_desc'] = $this->unionpay->outdata['msg_rsp_desc'] = $ret['message'];
                $app->updateOutData(false);

                return;
            }

            //成功
            if (isset($ret['msg_rsp_code']) && $ret['msg_rsp_code'] == '0000') {
                $app->setSign(true);
                $checksign = $app->checkSign(true, false);
                if ($checksign !== true) {
                    $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_code'] = 9996;
                    $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_desc'] = '获取优惠券数据验签失败。';
                    $app->updateOutData(false);

                    return;
                }

                if (!isset($ret['coupon_list']) || !isset($ret['coupon_list'][0]['coupon_no'])) {
                    $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_code'] = 9996;
                    $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_desc'] = '没有找到优惠券信息。';
                    $app->updateOutData(false);

                    return;
                }

                $coupon = [
                    'unionpay_log_id'     => $app->model->id,
                    'mobile'              => $this->unionpay->params['mobile'],
                    'openid'              => $this->unionpay->params['issue_user_id'] ?? '',
                    'event_no'            => $this->unionpay->params['event_no'],
                    'coupon_no'           => $ret['coupon_list'][0]['coupon_no'],
                    'effective_date_time' => Carbon::parse($ret['coupon_list'][0]['effective_date_time'])
                                                   ->format('Y-m-d H:i:s'),
                    'expire_date_time'    => Carbon::parse($ret['coupon_list'][0]['expire_date_time'])
                                                   ->format('Y-m-d H:i:s'),
                ];

                $info = UnionpayCoupon::create($coupon);
                if (!$info) {
                    $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_code'] = '9999';
                    $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_desc'] = '券码入库失败';
                    $app->updateOutData(false);

                    return;
                }

                $this->unionpay->outdata = array_merge($this->unionpay->outdata, [
                    'order_no'            => $ret['order_no'],
                    'coupon_no'           => $info->coupon_no,
                    'effective_date_time' => $info->effective_date_time,
                    'expire_date_time'    => $info->expire_date_time,
                    'mobile'              => $info->mobile,
                ]);
                $app->updateOutData(false);
            } else {
                $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_code'] = $ret['msg_rsp_code'];
                $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_desc'] = $ret['msg_rsp_desc'];
                $app->updateOutData(false);

            }

        } catch (\Exception $e) {
            $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_code'] = '9999';
            $app->outdata['msg_rsp_code'] = $this->unionpay->outdata['msg_rsp_desc'] = $e->getMessage();
            $app->updateOutData(false);

        }

    }

    public function back()
    {
        return $this->unionpay->outdata;
    }

}
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

    public    $app;

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

            $this->app = app('xuanchen.unionpay');
            $this->app->setSign(false);

            //设置入参
            $this->app->setParams($data);
            //校验入参
            $this->app->checkInData();

            $this->app->params['sign'] = $this->app->getSign(false);

            info('getCode Params' . json_encode($this->app->params));

            //入库
            $this->app->InputData();

            //申请发券
            $ret                = $this->app->sendPost($url, $this->app->params);
            $this->app->outdata = $ret;
            $this->app->sign    = $ret['sign'] ?? '';

            if (isset($ret['code']) && $ret['code'] == 0) {
                $this->setOutData('999', $ret['message']);

                return;
            }

            //成功
            if (isset($ret['msg_rsp_code']) && $ret['msg_rsp_code'] == '0000') {
                $this->app->setSign(true);
                $checksign = $this->app->checkSign(true, false);
                if ($checksign !== true) {
                    $this->setOutData('9996', '获取优惠券数据验签失败');

                    return;
                }

                if (!isset($ret['coupon_list']) || !isset($ret['coupon_list'][0]['coupon_no'])) {
                    $this->setOutData('9996', '没有找到优惠券信息.');

                    return;
                }

                $info = UnionpayCoupon::where('coupon_no', $ret['coupon_list'][0]['coupon_no'])->first();

                if (!$info) {
                    $coupon = [
                        'unionpay_log_id'     => $this->app->model->id,
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
                        $this->setOutData('9996', '券码入库失败.');

                        return;
                    }
                }

                $this->unionpay->outdata = array_merge($this->unionpay->outdata, [
                    'order_no'            => $ret['order_no'],
                    'coupon_no'           => $info->coupon_no,
                    'effective_date_time' => $info->effective_date_time,
                    'expire_date_time'    => $info->expire_date_time,
                    'mobile'              => $info->mobile,
                ]);
                $this->app->updateOutData(false);
            } else {

                $this->setOutData($ret['msg_rsp_code'], $ret['msg_rsp_desc']);

            }

        } catch (\Exception $e) {
            $this->setOutData(9999, $e->getMessage());
        }

    }

    /**
     * Notes: 更新错误信息
     * @Author: 玄尘
     * @Date  : 2020/12/24 8:18
     * @param $code
     * @param $msg
     */
    public function setOutData($code, $msg)
    {
        if ($this->app) {
            $this->app->outdata['msg_rsp_code'] = $code;
            $this->app->outdata['msg_rsp_desc'] = $msg;
            $this->app->updateOutData(false);
        }

        if ($this->unionpay) {
            $this->unionpay->outdata['msg_rsp_code'] = $code;
            $this->unionpay->outdata['msg_rsp_desc'] = $msg;
        }

    }

    public function back()
    {
        return $this->unionpay->outdata;
    }

}
<?php

namespace XuanChen\UnionPay;

use App\Models\User;
use XuanChen\UnionPay\Action\Init;
use XuanChen\Coupon\Coupon;
use XuanChen\UnionPay\Action\Query;
use XuanChen\UnionPay\Action\Redemption;
use XuanChen\UnionPay\Action\Reversal;
use Illuminate\Support\Str;
use XuanChen\UnionPay\Action\GetCode;
use XuanChen\UnionPay\Action\UpdateCode;
use XuanChen\UnionPay\Models\UnionpayLog;

/**
 * 银联入口
 */
class UnionPay extends Init
{

    /**
     * Notes: 设置参数
     * @Author: 玄尘
     * @Date  : 2020/11/10 10:45
     * @param $params
     */
    public function setParams($params)
    {
        $this->sign         = $params['sign'] ?? '';
        $this->params       = $params;
        $this->msg_txn_code = $params['msg_txn_code'] ?? '';
    }

    /**
     * Notes: 是否需要验签
     * @Author: 玄尘
     * @Date  : 2020/12/14 8:27
     */
    public function setSign($value)
    {
        $this->isSign = $value;
    }

    /**
     * Notes: 设置基础参数
     * @Author: 玄尘
     * @Date  : 2020/11/10 10:46
     */
    public function setConfig()
    {
        $this->this_type = config('unionpay.this_type');//设置环境
        if ($this->this_type == 'test') {
            $this->union_public = config('unionpay.certificate.test.union.public');
            $this->ysd_public   = config('unionpay.certificate.test.ysd.public');
            $this->ysd_private  = config('unionpay.certificate.test.ysd.private');
            $this->code_url     = config('unionpay.unionpay_url.test.code');
        } else {
            $this->union_public = config('unionpay.certificate.dev.union.public');
            $this->ysd_public   = config('unionpay.certificate.dev.ysd.public');
            $this->ysd_private  = config('unionpay.certificate.dev.ysd.private');
            $this->code_url     = config('unionpay.unionpay_url.dev.code');

        }
        $this->msg_sender  = config('unionpay.msg_sender');
        $this->agent_id    = config('unionpay.agent_id');
        $this->outlet_id   = config('unionpay.outlet_id');
        $this->startMemory = round(memory_get_usage() / 1024 / 1024, 2);
    }

    /**
     * Notes: 入口
     * @Author: 玄尘
     * @Date  : 2020/10/9 9:33
     */
    public function start()
    {
        //设置基础数据
        $this->getOutBaseData();

        try {
            //校验数据
            $this->checkInData();
            //查询是否是幂等 就是重复查询
            $this->idempotent();
            //入库请求参数
            $this->InputData();
            //返回值
            $this->out_data();
            //更新数据
            $this->updateOutData();
        } catch (\Exception $e) {

            $this->outdata['msg_rsp_code'] = '9999';
            $this->outdata['msg_rsp_desc'] = $e->getMessage() ?? '未知错误';
            if (empty($this->model->out_source)) {

                $this->updateOutData();

            }
        }

    }

    //处理流程
    public function out_data()
    {

        //是幂等
        if ($this->info && !empty($this->info->out_source)) {
            $this->outdata = $this->info->out_source;
        } else {
            if ($this->msg_rsp_code == '0000') {
                switch ($this->msg_txn_code) {
                    case '002025'://聚合营销优惠查询接口
                        $action = new Query($this);
                        $action->start();
                        $this->outdata = $action->back();
                        break;
                    case '002100'://销账交易接口
                        $action = new Redemption($this);
                        $action->start();
                        $this->outdata = $action->back();
                        break;
                    case '002101'://冲正
                    case '002102'://撤销
                        $action = new Reversal($this);
                        $action->start();
                        $this->outdata = $action->back();
                        break;
                    case 'code'://领券
                        $action = new GetCode($this);
                        $action->start();
                        $this->outdata = $action->back();
                        break;
                    case '012100'://核销通知
                        $action = new UpdateCode($this);
                        $action->start();
                        $this->outdata = $action->back();
                        break;
                    default:
                        break;
                }

            } else {
                $this->outdata['msg_rsp_code'] = $this->msg_rsp_code;
                $this->outdata['msg_rsp_desc'] = $this->msg_rsp_desc;
            }
        }

    }

    /**
     * Notes: 入库数据
     * @Author: 玄尘
     * @Date  : 2020/9/30 8:46
     */
    public function InputData()
    {
        if (!$this->msg_txn_code) {
            throw new \Exception('获取基础数据失败');
        }

        $regular = config('unionpay.regular');
        if (!isset($regular[$this->msg_txn_code])) {
            throw new \Exception('获取基础数据失败');
        }

        //获取基础数据
        $base = $regular[$this->msg_txn_code];

        $data = [];
        //循环获取入库数据
        foreach ($this->params as $key => $param) {
            if (in_array($key, $base)) {
                $data[$key] = $param;
            }
            $data['in_source'][$key] = $param;
           
        }

        $this->model = UnionpayLog::create($data);

        if (empty($this->model)) {
            throw new \Exception('数据入库失败');
        }
    }

    /**
     * Notes: 校验输入的数据
     * @Author: 玄尘
     * @Date  : 2020/9/30 14:46
     */
    public function checkInData()
    {
        try {
            //验签
            $res = $this->checkSign(false, false);
            if ($res !== true) {
                $this->msg_rsp_code = 9996;
                $this->msg_rsp_desc = '验签失败';
            }
        } catch (\Exception $e) {
            $this->msg_rsp_code = 9996;
            $this->msg_rsp_desc = $e->getMessage();
        }

        if ($this->msg_txn_code && $this->msg_rsp_code == '0000') {
            $rule_code = config('unionpay.validator')[$this->msg_txn_code];
            $rule_msg  = config('unionpay.fields')[$this->msg_txn_code]['in'];

            foreach ($rule_code as $item) {
                $rule[$item]              = 'required';
                $msg[$item . '.required'] = $rule_msg[$item] . '不能为空';
            }
            $validator = \Validator::make($this->params, $rule, $msg);

            if ($validator->fails()) {
                $this->msg_rsp_code = 9996;
                $this->msg_rsp_desc = $validator->errors()->first();

            }

        } else {

            $this->msg_rsp_code = 9996;
            $this->msg_rsp_desc = $this->msg_rsp_code == '0000' ? '平台流水号不能为空。' : $this->msg_rsp_desc;
        }

    }

    /**
     * Notes: 返回的基础数据
     * @Author: 玄尘
     * @Date  : 2020/9/30 14:48
     */
    public function getOutBaseData()
    {
        $basics = [
            "msg_type"      => $this->msg_type,
            "msg_txn_code"  => $this->msg_txn_code,
            "msg_crrltn_id" => $this->params['msg_crrltn_id'] ?? '',
            "msg_flg"       => 1,
            "msg_sender"    => $this->msg_sender,
            "msg_time"      => now()->format('YmdHis'),
            "msg_sys_sn"    => $this->params['msg_sys_sn'] ?? '',
            "msg_rsp_code"  => $this->msg_rsp_code,
            "msg_rsp_desc"  => $this->msg_rsp_desc,
        ];

        switch ($this->msg_txn_code) {
            //查询
            case '002025':
                $basics = array_merge($basics, [
                    "discount"    => 0,
                    "actual_amt"  => 0,
                    "pos_display" => "",
                    //                    "pos_receipt" => config('unionpay.pos_receipt'),
                    //                    "pos_ad"      => config('unionpay.pos_ad'),
                    "pos_mkt_ad"  => config('unionpay.pos_receipt'),
                ]);
                break;
            //销账
            case '002100':
                $basics = array_merge($basics, [
                    'msg_ver'      => 0.1,
                    'orig_amt'     => $this->params['orig_amt'],
                    'discount_amt' => $this->params['discount_amt'],
                    'pay_amt'      => $this->params['pay_amt'],
                    'serv_chg'     => config('unionpay.serv_chg'),
                    'commission'   => config('unionpay.commission'),
                    'event_no'     => '',//活动号 直接为空就可以
                ]);
                break;
            //冲正
            case '002101':
                //撤销
            case '002102':
                $basics = array_merge($basics, [
                    'msg_ver' => 0.1,
                ]);
                break;
            case 'openid':
                $basics = [
                    "msg_sender"    => $this->msg_sender,
                    "nonce_str"     => Str::random(32),
                    "timestamp"     => now()->timestamp,
                    "auth_scope"    => 'base_info',
                    "callback_type" => $this->params['callback_type'] ?? '',
                    "callback_url"  => $this->params['callback_url'] ?? '',
                    "right_id"      => $this->params['right_id'],
                ];
                break;
            case '106040':
                $basics = [];
                break;
            case 'code':
                $basics = [];
                break;
            case '012100':
                $basics = array_merge($basics, [
                    'msg_ver' => 0.1,
                ]);
                break;
            default:
                break;
        }

        return $this->outdata = $basics;

    }

    /**
     * Notes: 查询是否是幂等
     * @Author: 玄尘
     * @Date  : 2020/10/10 13:25
     */
    public function idempotent()
    {
        //没有req_serial_no 就是本时生活为了获取封装数据
        if (isset($this->params['req_serial_no'])) {
            $this->info = UnionpayLog::where('req_serial_no', $this->params['req_serial_no'])
                                     ->where('msg_txn_code', $this->msg_txn_code)
                                     ->where('status', 1)
                                     ->latest()
                                     ->first();
        }

    }

    /**
     * Notes: 更新返回值
     * @Author: 玄尘
     * @Date  : 2020/12/21 14:14
     * @param  bool  $sign  false 为银联返回数据 不校验
     * @throws \Exception
     */
    public function updateOutData($sign = true)
    {
        if ($sign) {
            $this->outdata['sign'] = $this->getSign();
        }

        //如果有入库模型
        if ($this->model) {
            $this->model->out_source = $this->outdata;
            if (isset($this->outdata['msg_rsp_code']) && $this->outdata['msg_rsp_code'] != '0000') {
                $this->model->status = 0;
            }
            $this->model->save();
        }
    }

}

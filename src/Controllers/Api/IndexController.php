<?php

namespace XuanChen\UnionPay\Controllers\Api;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use XuanChen\UnionPay\UnionPay;

class IndexController extends Controller
{

    use ValidatesRequests;

    public function index(Request $request)
    {

        $action = new UnionPay();
        $action->setConfig();
        $action->params = $request->all();
        //        $sign         = $action->getSign(false);
        $sign = $request->sign;
        //        $sign         = 'd8e5bf46d0d9f1da702170c2e141d85cf3ca785106886dbfedb3310ee9ce2ca3f18a2e6c179ec9908fc4f41d05df463634106918bdbefc63b8f199c7d2f3b0d45510b4dd6ccdf4549e11a8551a5098b14c01fdaa5840a4608f462fdafdc14b8f2a35471da315d8245a4ef6281b6e04bd22d5a266500a6caf6e5203202c37111d';
        $action->sign = $sign;
        $res          = $action->checkSign(false, true);
        dump('签名：' . $sign);
        $res_str = ($res === true) ? '成功' : '失败';
        dump('验签结果：' . $res_str);
        dd($action);

    }

    /**
     * Notes: 银联接口
     * @Author: 玄尘
     * @Date  : 2020/9/28 16:31
     * @param  Request  $request
     * @return mixed
     */
    public function query(Request $request)
    {
        $inputs = $request->all();
        $app    = app('xuanchen.unionpay');
        $nosign = config('unionpay.nosign');
        //调试开关
        $debug = config('unionpay.debug');

        if (in_array($inputs['msg_txn_code'], $nosign)) {
            $app->setSign(false);
        }
        $app->setParams($inputs);
        if ($debug) {
            //验签
            info('in sign：' . $app->sign);

        }
        $app->start();

        if ($debug) {
            //验签
            $app->sign = $app->outdata['sign'];
            $res       = $app->checkOutData();
            $res_str   = ($res === true) ? '成功' : '失败';
            info('out sign：' . $app->sign);
            info('验签结果：' . $res_str);
        }

        return $app->respond();
    }

    /**
     * Notes: 封装获取openid数据
     * @Author: 玄尘
     * @Date  : 2020/12/14 16:36
     */
    public function openid(Request $request)
    {
        $inputs = $request->all();

        $app = app('xuanchen.unionpay');
        $app->setParams($inputs);
        $app->start();

        return $app->respond();
    }

    /**
     * Notes: 领取优惠券
     * @Author: 玄尘
     * @Date  : 2020/12/15 11:13
     * @param  \Illuminate\Http\Request  $request
     */
    public function code(Request $request)
    {
        $inputs = $request->all();
        $app    = app('xuanchen.unionpay');
        $app->setParams($inputs);
        $app->start();

        return $app->respond();
    }

}

<?php

namespace XuanChen\UnionPay\Action;

use App\Exceptions\ApiException;
use App\Exceptions\ApiUnionpayException;
use App\Models\Log as LogModel;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class Init
{

    //环境
    public $this_type;

    //传入的参数
    public $params;

    //传入的签名
    public $sign;

    public $msg_type     = '00';

    public $msg_txn_code;

    public $msg_sender;

    public $msg_rsp_code = '0000';

    public $msg_rsp_desc = '成功';

    //入库的模型
    public $model;

    //返回的数据
    public $outdata;

    //网点id
    public $outlet_id;

    //渠道id
    public $agent_id;

    //日志
    public $log;

    //幂等数据
    public $info;

    //内存
    public $mem;

    //开始内存
    public $startMemory;

    //是否需要验签
    public $isSign = true;

    //银联公钥
    public $union_public;

    //亿时代公钥
    public $ysd_public;

    //亿时代私钥
    public $ysd_private;

    //微信侧领券地址
    public $code_url;

    /**
     * Notes: 验签
     * @Author: 玄尘
     * @Date  : 2020/9/30 8:39
     * @param  false  $self  是否是自己的证书
     * @return bool|string
     */
    public function checkSign($out = true, $self = false)
    {
        if (!$this->isSign) {
            return true;
        }

        $sign = $this->hexXbin($this->sign);
        if (!$sign) {
            throw new \Exception('签名错误');
        }
        $public_key = $this->getPublic($self);

        $pub_key_id = openssl_get_publickey($public_key);

        $signStr = $this->getSignString($out);

        if ($pub_key_id) {
            $result = (bool) openssl_verify($signStr, $sign, $pub_key_id);
            openssl_free_key($pub_key_id);
        } else {
            throw new \Exception('私钥格式有误');

        }

        return $result;
    }

    /**
     * Notes: 校验sign
     * @Author: 玄尘
     * @Date  : 2020/10/13 15:21
     * @param         $data
     * @param  false  $types
     * @return int|string
     */
    public function hexXbin($sign, $types = false)
    {
        // 过滤非16进制字符
        $checkStr = strspn($sign, '0123456789abcdefABCDEF');
        //字符串长度不是偶数时pack来处理
        if (strlen($checkStr) % 2) {
            return pack("H*", $sign);
        } else {
            return hex2bin($sign);
        }
    }

    /**
     * Notes: 签名
     * @Author: 玄尘
     * @Date  : 2020/10/9 15:52
     * @param  bool  $self
     * @return string
     * @throws \Exception
     */
    public function getSign($out = true)
    {
        $signStr = $this->getSignString($out);

        $private_key = $this->getPrivate();
        $privKeyId   = openssl_pkey_get_private($private_key);

        if (!$privKeyId) {
            throw new \Exception('私钥格式有误');
        }

        if (openssl_sign($signStr, $signature, $privKeyId)) {
            $signature = bin2hex($signature);
        } else {
            throw new \Exception('签名错误');
        }

        openssl_free_key($privKeyId);

        return $signature;
    }

    /**
     * Notes: 获取待签名字符串
     * @Author: 玄尘
     * @Date  : 2020/9/30 9:38
     * @param $out 是否是获取返回值的数据
     * @return string
     */
    public function getSignString($out = false)
    {
        if ($out) {
            $params = $this->outdata;
        } else {
            $params = $this->params;
        }

        $params = $this->checkSignData($params, $out);

        if (empty($params)) {
            throw new \Exception('获取校验数据失败，缺少数据..');
        }

        ksort($params);

        //http_build_query 会自动urlencode 需要转换
        $signStr = $this->str2utf8(urldecode(http_build_query($params)));

        //调试开关
        $debug = config('unionpay.debug');
        if ($debug) {
            if ($out) {
                info('type out ' . $signStr);
            } else {
                info('type in ' . $signStr);
            }
        }

        return $signStr;
    }

    /**
     * Notes: 格式化需要校验的数据
     * @Author: 玄尘
     * @Date  : 2021/2/18 15:47
     * @param $params
     */
    public function checkSignData($params, $out)
    {
        //需要校验的字段
        $checksigns  = config('unionpay.checksign');
        $checkparams = $params;

        if (isset($checksigns[$this->msg_txn_code])) {
            $checkfrom = $out ? 'out' : 'in';

            if ($checksigns[$this->msg_txn_code] && isset($checksigns[$this->msg_txn_code][$checkfrom])) {
                $checkparams = [];
                foreach ($params as $key => $param) {
                    if (in_array($key, $checksigns[$this->msg_txn_code][$checkfrom])) {
                        $checkparams[$key] = $param;
                    }
                }
            }
        }

        //排除sign
        if (isset($checkparams['sign'])) {
            unset($checkparams['sign']);
        }

        foreach ($checkparams as $key => &$param) {
            if ($param === 'null') {
                $param = '';
            }

            if (is_null($param)) {
                $param = '';
            }
        }

        return $checkparams;
        //
        //        $params = collect($params);
        //        $res    = $params->filter(function ($value, $key) {
        //            //                return strlen($value);
        //            return strlen($value);
        //        });
        //
        //        $res = collect($params);
        //
        //        return $res->toArray();

    }

    //获取私钥
    public function getPrivate()
    {
        $private = $this->ysd_private;

        if (!file_exists($private)) {
            throw new \Exception('缺少私钥文件');
        }

        return file_get_contents($private);
    }

    //获取公钥
    public function getPublic($self = false)
    {
        $public = $this->union_public;

        if ($self) {
            $public = $this->ysd_public;
        }

        return file_get_contents($public);
    }

    /**
     * Notes: 插入日志
     * @Author: 玄尘
     * @Date  : 2020/10/9 14:38
     * @return mixed
     */
    public function addLog()
    {
        $log_type = config('unionpay.log_type');
        $data     = [
            'path'       => request()->url(),
            'method'     => request()->method(),
            'type'       => $log_type[$this->msg_txn_code] ?? $this->msg_txn_code,
            'in_source'  => $this->params,
            'out_source' => $this->outdata,
        ];

        return $this->log = LogModel::create($data);
    }

    /**
     * 将字符串编码转为 utf8
     * @param $str
     * @return string
     */
    public function str2utf8($str)
    {
        $encode = mb_detect_encoding($str, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
        if ($encode != 'UTF-8') {
            $str = $str ? $str : mb_convert_encoding($str, 'UTF-8', $encode);
        }
        $str = is_string($str) ? $str : '';

        return $str;
    }

    //输出数据
    public function respond()
    {
        $rt = microtime(true) - LARAVEL_START;

        $header = [
            'rt'          => round($rt * 1000, 2) . 'ms',
            'qps'         => round(1 / $rt, 1), 'company' => 'YSD',
            'startMemory' => $this->startMemory,
            'endMemory'   => round(memory_get_usage() / 1024 / 1024, 2),
        ];

        $addlog = config('unionpay.log');
        if ($addlog) {
            $this->addLog();
        }

        return \Response::json($this->outdata, 200, $header);
    }

    /**
     * Notes: 到银联取优惠券
     * @Author: 玄尘
     * @Date  : 2020/12/15 11:23
     * @param $portUrl
     * @param $paramArray
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function sendPost($portUrl, $paramArray)
    {
        $client = new Client();

        try {
            $response = $client->request(
                'POST',
                $portUrl,
                [
                    'form_params' => $paramArray,
                    'http_errors' => false,
                    'timeout'     => 3,
                ]
            );

            if ($response->getStatusCode() == 200) {
                $body = $response->getBody();

                return json_decode($body->getContents(), true);
            } else {
                return ['code' => 0, 'message' => '接口错误 Post'];
            }
        } catch (\Exception $exception) {
            return ['code' => 0, 'message' => $exception->getMessage()];
        }

    }

    /**
     * Notes: 校验自己的签名
     * @Author: 玄尘
     * @Date  : 2021/2/18 15:20
     */
    public function checkOutData()
    {
        return $this->checkSign(true, true);
    }

}

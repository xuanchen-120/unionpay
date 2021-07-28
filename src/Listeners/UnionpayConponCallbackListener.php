<?php

namespace XuanChen\UnionPay\Listeners;

use GuzzleHttp\Client;
use Illuminate\Contracts\Queue\ShouldQueue;
use RuntimeException;
use XuanChen\UnionPay\Event\UnionpayConponCallback;

class UnionpayConponCallbackListener implements ShouldQueue
{

    public $queue = 'LISTENER';

    /**
     * Handle the event.
     * @param  XuanChen\UnionPay\Event\UnionpayConponCallback  $event
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function handle(UnionpayConponCallback $event)
    {
        $coupon = $event->coupon;

        $agent_id = config('unionpay.agent_id');
        $user     = config('unionpay.user_model')::find($agent_id);

        if ($user->callback) {
            $client = new Client();

            $response = $client->request('post', $user->callback, [
                'timeout' => 30,
                'query'   => [
                    'code'   => $coupon->coupon_no,
                    'status' => $coupon->status,
                ],
            ]);

            if ($response->getStatusCode() == 200) {
                $body   = $response->getBody();
                $result = json_decode($body->getContents(), true);
                $error  = false;

            } else {
                $remark = '接口错误';
                $error  = true;
            }

            if ($error) {
                throw new RuntimeException($remark);
            }
        }

    }

}

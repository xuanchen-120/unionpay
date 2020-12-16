<?php

namespace XuanChen\UnionPay\Renderable;

use Encore\Admin\Widgets\Table;
use Illuminate\Contracts\Support\Renderable;
use XuanChen\UnionPay\Models\UnionpayLog;

class OutData implements Renderable
{

    public function render($key = null)
    {
        $log        = UnionpayLog::find($key);
        $out_source = $log->out_source;

        if (is_array($out_source) && count($out_source) > 1) {
            unset($out_source['sign']);
            $table = new Table(['名称', '值'], $out_source, ['panel ', 'panel-success']);

            return $table->render();
        }
    }

}
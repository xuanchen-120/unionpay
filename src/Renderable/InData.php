<?php

namespace XuanChen\UnionPay\Renderable;

use Encore\Admin\Widgets\Table;
use Illuminate\Contracts\Support\Renderable;
use XuanChen\UnionPay\Models\UnionpayLog;

class InData implements Renderable
{

    public function render($key = null)
    {
        $log       = UnionpayLog::find($key);
        $in_source = $log->in_source;

        if (is_array($in_source) && count($in_source) > 1) {
            $table = new Table(['名称', '值'], $in_source, ['panel', ' panel-default']);

            return $table->render();
        }
    }

}
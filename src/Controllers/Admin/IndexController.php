<?php

namespace XuanChen\UnionPay\Controllers\Admin;

use Auth;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use XuanChen\UnionPay\Models\UnionpayLog;
use XuanChen\UnionPay\Renderable\InData;
use XuanChen\UnionPay\Renderable\OutData;

class IndexController extends AdminController
{

    protected $title = '银联操作日志';

    /**
     * Notes:
     * @Author: <C.Jason>
     * @Date  : 2019/9/18 14:50
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UnionpayLog);

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->model()->orderBy('id', 'desc');

        $grid->filter(function ($filter) {
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('msg_txn_code', '交易类型')->select(config('unionpay.type'));
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('req_serial_no', '流水号');
                $filter->equal('orig_req_serial_no', '原流水号');
            });
        });

        $grid->column('id', '#ID#');
        $grid->column('msg_txn_code', '交易类型')
             ->using(config('unionpay.type'))
             ->label();

        //        $grid->column('msg_crrltn_id', '消息关联号');
        $grid->column('msg_time', '报文日期');
        $grid->column('mkt_code', '券码');
        $grid->column('msg_sys_sn', '平台流水号');
        $grid->column('req_serial_no', '流水号');
        $grid->column('orig_req_serial_no', '原流水号');
        $grid->column('status', '状态')
             ->using(UnionpayLog::STATUS)
             ->label([
                 0 => 'success',
                 1 => 'warning',
             ]);

        $grid->column('in_source', '请求参数')
             ->display(function ($title, $column) {
                 return '点击展开';
             })->modal(InData::class);

        $grid->column('out_source', '返回参数')
             ->display(function ($title, $column) {
                 return '点击展开';
             })->modal(OutData::class);

        //        $grid->column('sett_date', '清算日期');
        $grid->column('created_at', '注册时间');
        $grid->disableExport(false);

        $grid->export(function ($export) {
            $export->filename($this->title . date("YmdHis"));
        });

        return $grid;
    }

}

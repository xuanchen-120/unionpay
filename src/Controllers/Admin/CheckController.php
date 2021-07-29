<?php

namespace XuanChen\UnionPay\Controllers\Admin;

use Auth;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use XuanChen\UnionPay\Models\UnionpayCheck;

class CheckController extends AdminController
{

    protected $title = '对账单日志';

    /**
     * Notes: description
     * @Author: 玄尘
     * @Date  : 2021/7/29 10:39
     * @return \Encore\Admin\Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UnionpayCheck());

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->model()->orderBy('id', 'desc');

        $grid->column('id', '#ID#');
        $grid->column('sender', 'SP机构代码');
        $grid->column('date', '日期');
        $grid->column('number', '当天交易笔数');
        $grid->column('total', '当天交易金额');
        $grid->column('commission', '当天营销佣金总金额');
        $grid->column('created_at', '注册时间');
        $grid->disableExport(false);

        $grid->export(function ($export) {
            $export->filename($this->title . date("YmdHis"));
        });

        return $grid;
    }

}

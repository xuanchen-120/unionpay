<?php

namespace XuanChen\UnionPay\Controllers\Admin;

use App\Models\User;
use Auth;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use XuanChen\UnionPay\Models\UnionpayCheckLog;
use XuanChen\UnionPay\Models\UnionpayLog;

class CheckLogController extends AdminController
{

    protected $title = '对账单列表';

    /**
     * Notes:
     * @Author: <C.Jason>
     * @Date  : 2019/9/18 14:50
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UnionpayCheckLog());

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->model()->orderBy('id', 'desc');

        $grid->filter(function ($filter) {
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('msg_txn_code', '交易类型')->select(config('unionpay.type'));
                $users = User::whereHas('identity', function ($query) {
                    $query->where('identity_id', 1);
                })->get()->pluck('nickname', 'id');

                $filter->equal('outlet.parent_id', '渠道')->select($users);
                $filter->equal('status', '状态')->select(UnionpayCheckLog::STATUS);

            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('req_serial_no', '流水号');
                $filter->equal('orig_req_serial_no', '原流水号');
            });
        });

        $grid->column('id', '#ID#');

        $grid->column('网点名称/编号')->display(function () {
            return $this->outlet ? $this->outlet->nickname : $this->outletId;
        });
        $grid->column('msg_txn_code', '交易类型')
             ->using(config('unionpay.type'))
             ->label();
        $grid->column('code', '卡券编号');
        $grid->column('sett_date', '清算日期');
        $grid->column('total', '交易金额（分）');
        $grid->column('price', '优惠金额（分）');
        $grid->column('amount', '原交易金额（分）');
        $grid->column('status', '状态')
             ->using(UnionpayCheckLog::STATUS)
             ->label([
                 1 => 'success',
                 2 => 'warning',
             ]);

        $grid->column('req_serial_no', '流水号');
        $grid->column('orig_req_serial_no', '原流水号');
        $grid->column('source', '全部数据')->hide();

        $grid->disableExport(false);

        $grid->export(function ($export) {
            $export->column('status', function ($value, $original) {
                return strip_tags($value);
            });
            $export->column('msg_txn_code', function ($value, $original) {
                return strip_tags($value);
            });
            $export->column('total', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('price', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('amount', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('req_serial_no', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('orig_req_serial_no', function ($value, $original) {
                return $value . "\t";
            });
            $export->filename($this->title . date("YmdHis"));
        });

        return $grid;
    }

}

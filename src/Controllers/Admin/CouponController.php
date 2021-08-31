<?php

namespace XuanChen\UnionPay\Controllers\Admin;

use Auth;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Grid;
use XuanChen\UnionPay\Models\UnionpayLog;
use XuanChen\UnionPay\Renderable\InData;
use XuanChen\UnionPay\Renderable\OutData;
use XuanChen\UnionPay\Models\UnionpayCoupon;

class CouponController extends AdminController
{

    protected $title = '银联优惠券管理';

    /**
     * Notes:
     * @Author: <C.Jason>
     * @Date  : 2019/9/18 14:50
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new UnionpayCoupon);

        $grid->disableActions();
        $grid->disableCreateButton();
        $grid->model()->orderBy('id', 'desc');

        $grid->filter(function ($filter) {
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('mobile', '手机号');
                $filter->equal('status', '状态')->select(UnionpayCoupon::STATUS);
                $filter->equal('event_no', '活动号');
            });
            $filter->column(1 / 2, function ($filter) {
                $filter->equal('req_serial_no', '流水号');
                $filter->equal('orig_req_serial_no', '原流水号');
                $filter->equal('shop_no', '门店号');
            });
        });

        $grid->column('id', '#ID#');
        $grid->column('mobile', '手机号');
        $grid->column('openid', 'Openid')->hide();
        $grid->column('coupon_no', '券码');
        $grid->column('event_no', '活动号');
        $grid->column('orig_amt', '原始金额(元)')
             ->display(function () {
                 return $this->orig_amt / 100;
             })
             ->decimal();
        $grid->column('discount_amt', '优惠的金额(元)')
             ->display(function () {
                 return $this->discount_amt / 100;
             })
             ->decimal();

        $grid->column('pay_amt', '支付金额(元)')
             ->display(function () {
                 return $this->pay_amt / 100;
             })
             ->decimal();

        $grid->column('effective_date_time', '券码生效时间');
        $grid->column('expire_date_time', '券码过期时间');
        $grid->column('shop_no', '门店号');
        $grid->column('门店名称')->display(function () {
            return $this->user ? $this->user->info->nickname : '---';
        });
        $grid->column('trans_crrltn_no', '交易关联流水号')->hide();
        $grid->column('order_no', '订单号')->hide();
        $grid->column('status', '状态')
             ->using(UnionpayCoupon::STATUS)
             ->label([
                 1 => 'success',
                 2 => 'warning',
             ]);

        $grid->column('created_at', '获取时间');
        $grid->disableExport(false);

        $grid->export(function ($export) {
            $export->column('mobile', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('orig_amt', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('discount_amt', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('coupon_no', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('shop_no', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('pay_amt', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('order_no', function ($value, $original) {
                return $value . "\t";
            });
            $export->column('status', function ($value, $original) {
                return strip_tags($value);
            });
            $export->filename($this->title . date("YmdHis"));
        });

        return $grid;
    }

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnionpayCouponsTable extends Migration
{

    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('unionpay_coupons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('unionpay_log_id');
            $table->string('mobile');
            $table->string('openid');
            $table->string('coupon_no')->comment('活动号');
            $table->string('event_no');
            $table->boolean('status');
            $table->decimal('orig_amt', 10, 2)->nullable()->comment('原始金额');
            $table->decimal('discount_amt', 10, 2)->nullable()->comment('优惠的金额');
            $table->decimal('pay_amt', 10, 2)->nullable()->comment('支付金额');
            $table->string('effective_date_time')->comment('券码生效时间');
            $table->string('expire_date_time')->comment('券码过期时间');
            $table->string('req_serial_no')->nullable();
            $table->string('shop_no')->nullable()->comment('门店号');
            $table->string('trans_crrltn_no')->nullable()->comment('交易关联流水号');
            $table->string('order_no')->nullable()->comment('订单号');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unionpay_coupons');
    }

}

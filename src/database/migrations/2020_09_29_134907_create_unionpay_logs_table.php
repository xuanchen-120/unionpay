<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnionpayLogsTable extends Migration
{

    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('unionpay_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('msg_type', 2)->comment('报文类型');
            $table->string('msg_txn_code', 10)->comment('交易代码')->index();
            $table->string('msg_crrltn_id', 32)->comment('消息关联号');
            $table->string('msg_sender', 10)->comment('报文发送方');
            $table->boolean('status', 1)->comment('1正常 0失败')->default(1);
            $table->string('msg_time', 20)->comment('报文日期');
            $table->string('msg_ver', 10)->comment('报文版本号');
            $table->string('mkt_code', 100)->comment('券码')->index();
            $table->string('msg_sys_sn', 40)->comment('平台流水号');
            $table->string('req_serial_no', 50)->comment('流水号');
            $table->string('orig_req_serial_no', 50)->comment('原流水号')->nullable();
            $table->text('in_source')->comment('入参');
            $table->text('out_source')->comment('出参')->nullable();
            $table->string('sett_date', 20)->comment('清算日期')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unionpay_logs');
    }

}

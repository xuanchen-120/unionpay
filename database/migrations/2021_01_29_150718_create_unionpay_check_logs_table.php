<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnionpayCheckLogsTable extends Migration
{

    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('unionpay_check_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('unionpay_check_id')->index();
            $table->string('msg_txn_code', 10)->comment('交易代码')->index();
            $table->string('code')->comment('券码');
            $table->string('shop')->comment('门店号');
            $table->string('total')->comment('交易金额');
            $table->string('price')->comment('优惠金额');
            $table->string('amount')->comment('原交易金额');
            $table->string('req_serial_no', 50)->nullable()->comment('流水号');
            $table->string('sett_date', 20)->nullable()->comment('清算日期');
            $table->text('source')->nullable()->comment('全部数据');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unionpay_check_logs');
    }

}

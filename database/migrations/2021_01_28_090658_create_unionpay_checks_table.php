<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnionpayChecksTable extends Migration
{

    /**
     * Run the migrations.
     * @return void
     */
    public function up()
    {
        Schema::create('unionpay_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sender');
            $table->string('date');
            $table->integer('number');
            $table->string('total');
            $table->string('commission');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unionpay_checks');
    }

}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone')->unique();
            $table->string('password')->nullable();
            $table->string('otp');
            $table->string('token')->nullable();
            $table->text('profile')->nullable();
            $table->double('last_lat')->nullable();
            $table->double('last_lng')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('pin_code')->nullable();
            $table->dateTime('last_seen')->nullable();
            $table->double('wallet_balance')->default(0);
            $table->integer('is_email_verified')->default(0);
            $table->integer('is_premium')->default(0);
            $table->integer('is_blocked')->default(0);
            $table->dateTime('subscription_ends')->nullable();
            $table->string('referral_code')->default("");
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}

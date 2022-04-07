<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('cfp_auth_id');
            $table->string('cfp_secret_key');
            $table->string('cf_app_id');
            $table->string('cf_secret_key');
            $table->string('cfp_auth');
            $table->string('cfp_auth_expiry');
            $table->integer('free_ads_count');
            $table->integer('free_withdrawal_count');
            $table->text('tnc');
            $table->text('privacy_policy');
            $table->text('about_us');
            $table->text('contact_us');
            $table->text('monthly_subs_price');
            $table->text('yearly_subs_price');
            $table->text('from_refer_amount');
            $table->text('to_refer_amount');
            $table->text('gcm_auth');
            $table->string('sms_api_key');
            $table->string('sms_senderid');
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
        Schema::dropIfExists('settings');
    }
}

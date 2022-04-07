<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvertisementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advertisements', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('category_id');
            $table->string('ads_title');
            $table->string('primary_image');
            $table->text('ads_description');
            $table->double('lat');
            $table->double('lng');
            $table->string('address');
            $table->double('price');
            $table->integer('status')->default(1);
            $table->integer('contact_count')->default(0);
            $table->integer('is_deleted')->default(0);
            $table->integer('is_hidden')->default(1);
            $table->integer('is_chat_available')->default(1);
            $table->integer('is_call_available')->default(1);
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
        Schema::dropIfExists('advertisements');
    }
}

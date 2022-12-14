<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Facebookuser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ry_socin_facebookusers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger("user_id")->nullable();
            $table->char("fbid")->nullable();
            $table->char("access_token");    
            $table->char("fbusername")->nullable();
            $table->char("firstname")->nullable();
            $table->char("lastname")->nullable();
            $table->char("gender")->nullable();
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
        Schema::drop('ry_socin_facebookusers');
    }
}

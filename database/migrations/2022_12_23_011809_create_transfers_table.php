<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->string('station_1_id');
            $table->string('station_2_id');
            $table->integer('time');
        });

        Schema::table('transfers', function (Blueprint $table) {
            $table->foreign('station_1_id')->references('id')->on('stations')->constrained()->onUpdate('restrict')->onDelete('restrict');
            $table->foreign('station_2_id')->references('id')->on('stations')->constrained()->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transfers');
    }
};

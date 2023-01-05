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
        Schema::create('station_station', function (Blueprint $table) {
            $table->id();
            $table->string('station');
            $table->string('connected_station');

            $table->foreign('station')->references('id')->on('stations')->constrained()->onUpdate('restrict')->onDelete('restrict');
            $table->foreign('connected_station')->references('id')->on('stations')->constrained()->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('station_station');
    }
};

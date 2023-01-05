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
        Schema::create('stops', function (Blueprint $table) {
            $table->id();
            $table->string('line_id');
            $table->string('station_id');
            $table->string('stop_number');

            $table->foreign('line_id')->references('id')->on('lines')->constrained()->onUpdate('restrict')->onDelete('restrict');
            $table->foreign('station_id')->references('id')->on('stations')->constrained()->onUpdate('restrict')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('routes');
    }
};

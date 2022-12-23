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
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('station_id');
            $table->string('name');
            $table->decimal('latitude', 8, 6);
            $table->decimal('longitude', 8, 6);
            $table->jsonb('served_by');
            $table->jsonb('connected_stations')->default('{}');

            $table->unique('station_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stations');
    }
};

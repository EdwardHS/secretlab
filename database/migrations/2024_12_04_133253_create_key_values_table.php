<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('key_values', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->json('values');
            $table->bigInteger('timestamp');
            $table->timestamps();
            $table->softDeletes();
            

            $table->index('timestamp');
            $table->index(['key', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('key_values');
    }
};

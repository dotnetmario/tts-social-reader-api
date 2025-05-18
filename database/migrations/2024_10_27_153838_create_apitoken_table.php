<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('apitokens', function (Blueprint $table) {
            $table->id();
            // $table->string('api_key');
            $table->string('model');
            $table->dateTime('expires_at')->nullable();
            $table->unsignedBigInteger('tokens');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apitokens');
    }
};

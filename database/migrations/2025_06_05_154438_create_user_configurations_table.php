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
        Schema::create('user_configurations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            // SSML_VOICE_GENDER_UNSPECIFIED => 0 / MALE => 1 / FEMALE => 2 / NEUTRAL => 3
            $table->enum('voice_gender', [0, 1, 2, 3])->default(2);
            $table->string('language_code')->default("en-US");

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_configurations');
    }
};

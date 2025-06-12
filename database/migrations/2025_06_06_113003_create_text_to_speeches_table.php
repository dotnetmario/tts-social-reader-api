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
        Schema::create('text_to_speeches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');

            $table->text('text');
            $table->string('path_to_file')->nullable();
            // $table->boolean('successful')->default(true);
            $table->enum('status', ['pending', 'failed', 'completed'])->default('pending');
            $table->unsignedInteger('characters_used');
            $table->json('credit_usages')->nullable();


            $table->string('voice_name')->nullable();
            $table->string('language_code')->nullable();
            // $table->enum('voice_type', ['standard', 'neural'])->nullable();
            $table->enum('voice_gender', [0, 1, 2, 3])->nullable();
            $table->text('error_message')->nullable();

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
        Schema::dropIfExists('text_to_speeches');
    }
};

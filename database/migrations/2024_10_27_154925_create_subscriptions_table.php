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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description');
            $table->float('price');
            $table->unsignedBigInteger('characters'); // amount of characters per subscription
            $table->boolean('active')->default(true);
            $table->string('paypal_plan_id')->nullable(); // for paypal subscriptions

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tsr_samples', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('customer_description',500);
            $table->string('description',500)->nullable();
            $table->boolean('is_disposed')->default(0);
            $table->bigInteger('tsr_id')->unsigned()->index();
            $table->foreign('tsr_id')->references('id')->on('tsrs')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tsr_samples');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create("tagwarehouse", function (Blueprint $table) {
            $table->increments("id");
            $table->unsignedInteger("warehouse_id")->index();
            $table
                ->foreign("warehouse_id")
                ->references("id")
                ->on("warehouse");
            $table->unsignedInteger("material_id")->index();
            $table
                ->foreign("material_id")
                ->references("id")
                ->on("materials");
            $table->string("material_code")->nullable();
            $table->string("material_name")->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists("tagwarehouse");
    }
};

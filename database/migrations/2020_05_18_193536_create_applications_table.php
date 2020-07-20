<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $applicationTable = config('samlidp.application_table');
        if (!Schema::hasTable($applicationTable)) {
            Schema::create($applicationTable, function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('app_icon')->nullable();
                $table->string('name')->nullable();
                $table->string('entity_id');
                $table->string('acs_callback')->nullable();
                $table->string('sls_callback')->nullable();
                $table->text('certificate')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('samlidp.application_table'));
    }
}

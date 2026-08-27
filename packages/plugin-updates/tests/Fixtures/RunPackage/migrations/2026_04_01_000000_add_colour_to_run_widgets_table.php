<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Shipped with release 0.0.4. The last of the four, so it is the one a run that died in the middle never reaches. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('run_widgets', function (Blueprint $table) {
            $table->string('colour')->nullable();
        });
    }
};

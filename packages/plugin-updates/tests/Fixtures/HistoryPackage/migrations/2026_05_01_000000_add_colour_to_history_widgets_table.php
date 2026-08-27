<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Shipped with release 0.5.0, alongside create_history_tags_table. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('history_widgets', function (Blueprint $table) {
            $table->string('colour')->nullable();
        });
    }
};

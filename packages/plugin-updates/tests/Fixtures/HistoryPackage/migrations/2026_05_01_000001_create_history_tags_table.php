<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Shipped with release 0.5.0, alongside add_colour_to_history_widgets_table. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_tags', function (Blueprint $table) {
            $table->id();
            $table->string('label');
        });
    }
};

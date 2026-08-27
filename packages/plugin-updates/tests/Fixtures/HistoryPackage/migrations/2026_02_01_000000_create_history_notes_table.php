<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Shipped with release 0.2.0. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('history_notes', function (Blueprint $table) {
            $table->id();
            $table->text('body');
        });
    }
};

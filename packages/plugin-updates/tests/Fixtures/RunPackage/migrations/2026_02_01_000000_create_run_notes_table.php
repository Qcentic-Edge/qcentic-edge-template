<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Shipped with release 0.0.2. The table the fixture seeder writes a row into. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('run_notes', function (Blueprint $table) {
            $table->id();
            $table->string('body');
        });
    }
};

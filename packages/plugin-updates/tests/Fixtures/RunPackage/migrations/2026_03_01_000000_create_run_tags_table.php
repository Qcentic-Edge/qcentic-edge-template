<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use QcenticEdge\PluginUpdates\Tests\Fixtures\MidRunFailure;

/**
 * Shipped with release 0.0.3. The third of the four, and the one a test can
 * make fail — which is how the resume guarantee gets asserted end to end: a
 * host with a request timeout dies somewhere in the middle of a long catch-up,
 * and everything before that point has to survive.
 */
return new class extends Migration
{
    public function up(): void
    {
        MidRunFailure::detonate();

        Schema::create('run_tags', function (Blueprint $table) {
            $table->id();
            $table->string('label');
        });
    }
};

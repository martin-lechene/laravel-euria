<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('euria.conversations.messages_table', 'euria_conversation_messages'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained(config('euria.conversations.table', 'euria_conversations'))->cascadeOnDelete();
            $table->string('role'); // system, user, assistant
            $table->text('content');
            $table->json('tool_calls')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('euria.conversations.messages_table', 'euria_conversation_messages'));
    }
};

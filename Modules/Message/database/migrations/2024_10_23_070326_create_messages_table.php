<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Chat\Models\Chat;
use Modules\Group\Models\Group;
use Modules\Message\Enums\MessageStatus;
use Modules\Message\Models\Message;
use Modules\User\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->morphs('messageable');
            $table->foreignIdFor(User::class, 'sender_id')->constrained('users');
            $table->text('content');
            $table->enum('status', get_enum_values(MessageStatus::cases()))->default(MessageStatus::PENDING->value);
            $table->boolean('is_edited')->default(false);
            $table->timestamp('sent_at');
        });

        Schema::create('message_member', function (Blueprint $table) {
            $table->foreignIdFor(Message::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'member_id')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('message_member');
    }
};

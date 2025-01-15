<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Group\Models\Group;
use Modules\User\Models\User;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('group_id')->unique();
            $table->foreignIdFor(User::class, 'owner_id')->constrained('users');
            $table->string('name');
            $table->string('picture')->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('group_member', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Group::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class, 'member_id')->constrained('users');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('joined_at')->default(now());
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups');
        Schema::dropIfExists('group_member');
    }
};

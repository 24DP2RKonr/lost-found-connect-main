<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_admin')) {
                $table->boolean('is_admin')->default(false)->after('remember_token');
            }
        });

        Schema::table('profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('profiles', 'user_id')) {
                $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('profiles', 'name')) {
                $table->string('name')->default('');
            }
            if (! Schema::hasColumn('profiles', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'location')) {
                $table->string('location')->nullable();
            }
            if (! Schema::hasColumn('profiles', 'avatar_url')) {
                $table->text('avatar_url')->nullable();
            }
        });

        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'user_id')) {
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('listings', 'title')) {
                $table->string('title');
            }
            if (! Schema::hasColumn('listings', 'description')) {
                $table->text('description');
            }
            if (! Schema::hasColumn('listings', 'type')) {
                $table->string('type'); // lost|found
            }
            if (! Schema::hasColumn('listings', 'category')) {
                $table->string('category');
            }
            if (! Schema::hasColumn('listings', 'location')) {
                $table->string('location');
            }
            if (! Schema::hasColumn('listings', 'date')) {
                $table->date('date')->nullable();
            }
            if (! Schema::hasColumn('listings', 'image')) {
                $table->text('image')->nullable();
            }
            if (! Schema::hasColumn('listings', 'views')) {
                $table->unsignedInteger('views')->default(0);
            }
        });

        Schema::table('conversations', function (Blueprint $table) {
            if (! Schema::hasColumn('conversations', 'listing_id')) {
                $table->foreignId('listing_id')->nullable()->constrained('listings')->nullOnDelete();
            }
            if (! Schema::hasColumn('conversations', 'sender_id')) {
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('conversations', 'receiver_id')) {
                $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('conversations', 'listing_title')) {
                $table->string('listing_title')->default('');
            }
        });

        Schema::table('messages', function (Blueprint $table) {
            if (! Schema::hasColumn('messages', 'conversation_id')) {
                $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('messages', 'sender_id')) {
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            }
            if (! Schema::hasColumn('messages', 'text')) {
                $table->text('text');
            }
            if (! Schema::hasColumn('messages', 'read')) {
                $table->boolean('read')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['text', 'read']);
            $table->dropConstrainedForeignId('sender_id');
            $table->dropConstrainedForeignId('conversation_id');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropColumn(['listing_title']);
            $table->dropConstrainedForeignId('receiver_id');
            $table->dropConstrainedForeignId('sender_id');
            $table->dropConstrainedForeignId('listing_id');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'type', 'category', 'location', 'date', 'image', 'views']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['name', 'phone', 'location', 'avatar_url']);
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin']);
        });
    }
};


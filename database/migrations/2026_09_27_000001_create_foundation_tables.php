<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('phone')->nullable()->index();
            $table->string('alternate_phone')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['last_name', 'first_name']);
        });
        Schema::create('staff', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('person_id')->unique()->constrained('people')->restrictOnDelete();
            $table->string('staff_number')->unique();
            $table->string('password');
            $table->string('status')->default('ACTIVE');
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('name');
            foreach (['legal_name', 'registration_number', 'email', 'phone', 'alternate_phone', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'logo_path'] as $field) {
                $table->string($field)->nullable();
            }
            $table->string('country')->default('Nigeria');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->unique()->constrained()->restrictOnDelete();
            $table->string('currency', 3)->default('NGN');
            $table->string('timezone')->default('Africa/Lagos');
            $table->string('date_format')->default('d/m/Y');
            $table->string('time_format')->default('H:i');
            foreach (['low_stock_notification', 'allow_customer_online_orders', 'allow_cash_payments', 'allow_bank_transfer', 'allow_pos_payments', 'online_payment_enabled'] as $field) {
                $table->boolean($field)->default(true);
            }
            $table->string('default_payment_provider')->nullable();
            foreach (['invoice' => 'INV', 'receipt' => 'RCT', 'order' => 'ORD', 'sale' => 'SAL'] as $field => $prefix) {
                $table->string($field.'_prefix')->default($prefix);
            }
            $table->timestamps();
        });
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 100);
            $table->string('key', 100);
            $table->text('value')->nullable();
            $table->string('type');
            $table->boolean('is_public')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['group', 'key']);
        });
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('prefix');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->unsignedInteger('padding')->default(6);
            $table->string('reset_frequency')->default('YEARLY');
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();
        });
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('event')->index();
            $table->morphs('subject');
            $table->text('description');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('metadata')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        foreach (['activity_logs', 'number_sequences', 'system_settings', 'business_settings', 'businesses', 'staff', 'people'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

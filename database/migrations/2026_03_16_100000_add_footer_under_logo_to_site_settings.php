<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function defaultFooterUnderLogo(): string
    {
        return '<p><a href="mailto:themesflat@gmail.com" class="link text-body-2 text_black">themesflat@gmail.com</a></p><p class="text-body-2">152 Thatcher Road St, Manhattan, NY 10463, <br>США</p><p class="text-body-2">(+068) 568 9696</p>';
    }

    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }
        if (! Schema::hasColumn('site_settings', 'footer_under_logo')) {
            Schema::table('site_settings', function (Blueprint $table) {
                $table->text('footer_under_logo')->nullable()->after('logo');
            });
            $default = $this->defaultFooterUnderLogo();
            DB::table('site_settings')->update(['footer_under_logo' => $default]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings') || ! Schema::hasColumn('site_settings', 'footer_under_logo')) {
            return;
        }
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn('footer_under_logo');
        });
    }
};

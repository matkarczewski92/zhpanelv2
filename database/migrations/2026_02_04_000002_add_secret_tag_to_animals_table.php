<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            $table->string('secret_tag', 10)->nullable()->after('public_profile_tag');
            $table->unique('secret_tag', 'animals_secret_tag_unique');
        });

        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length = 6;

        DB::table('animals')
            ->select(['id'])
            ->whereNull('secret_tag')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($alphabet, $length): void {
                foreach ($rows as $row) {
                    do {
                        $tag = '';
                        for ($i = 0; $i < $length; $i++) {
                            $tag .= $alphabet[random_int(0, strlen($alphabet) - 1)];
                        }
                    } while (DB::table('animals')->where('secret_tag', $tag)->exists());

                    DB::table('animals')
                        ->where('id', $row->id)
                        ->update(['secret_tag' => $tag]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('animals', function (Blueprint $table): void {
            $table->dropUnique('animals_secret_tag_unique');
            $table->dropColumn('secret_tag');
        });
    }
};

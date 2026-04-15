<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('musicians')) {
            return;
        }

        if (! Schema::hasColumn('musicians', 'experience_started_on')) {
            Schema::table('musicians', function (Blueprint $table): void {
                $table->date('experience_started_on')->nullable()->after('bio');
            });
        }

        if (Schema::hasColumn('musicians', 'years_of_experience')) {
            DB::table('musicians')
                ->whereNotNull('years_of_experience')
                ->orderBy('id')
                ->select(['id', 'years_of_experience'])
                ->chunkById(200, function ($rows): void {
                    foreach ($rows as $row) {
                        $years = (int) $row->years_of_experience;
                        if ($years < 1) {
                            continue;
                        }
                        $started = Carbon::now()->startOfMonth()->subYears($years);
                        DB::table('musicians')->where('id', $row->id)->update([
                            'experience_started_on' => $started->toDateString(),
                        ]);
                    }
                });

            Schema::table('musicians', function (Blueprint $table): void {
                $table->dropColumn('years_of_experience');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('musicians')) {
            return;
        }

        if (! Schema::hasColumn('musicians', 'years_of_experience')) {
            Schema::table('musicians', function (Blueprint $table): void {
                $table->unsignedSmallInteger('years_of_experience')->nullable()->after('bio');
            });
        }

        if (Schema::hasColumn('musicians', 'experience_started_on')) {
            Schema::table('musicians', function (Blueprint $table): void {
                $table->dropColumn('experience_started_on');
            });
        }
    }
};

<?php

declare(strict_types=1);

use App\Models\Musician;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('musicians')) {
            return;
        }

        Musician::query()->orderBy('id')->each(function (Musician $m): void {
            $anyDirty = false;
            foreach (['layout_draft', 'layout_published'] as $attr) {
                $layout = $m->{$attr};
                if (! is_array($layout) || empty($layout['blocks']) || ! is_array($layout['blocks'])) {
                    continue;
                }
                $attrDirty = false;
                foreach ($layout['blocks'] as $i => $b) {
                    if (! is_array($b)) {
                        continue;
                    }
                    if (($b['id'] ?? '') === 'bio') {
                        $layout['blocks'][$i]['id'] = 'description';
                        $attrDirty = true;
                    }
                }
                if ($attrDirty) {
                    $m->{$attr} = $layout;
                    $anyDirty = true;
                }
            }
            if ($anyDirty) {
                $m->saveQuietly();
            }
        });

        if (Schema::hasColumn('musicians', 'bio')) {
            Schema::table('musicians', function (Blueprint $table): void {
                $table->dropColumn('bio');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('musicians')) {
            return;
        }

        if (! Schema::hasColumn('musicians', 'bio')) {
            Schema::table('musicians', function (Blueprint $table): void {
                $table->text('bio')->nullable()->after('description');
            });
        }
    }
};

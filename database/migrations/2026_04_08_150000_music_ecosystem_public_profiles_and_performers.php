<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropUniqueNameIndexes();

        $this->createSchoolsTable();

        $this->extendMusicians();
        $this->extendTeachers(); // teachers before teacher_city FK? teacher_city references teachers - create after extend teachers
        $this->extendPeformers();
        $this->extendStudios();
        $this->extendRehearsals();
        $this->createTeacherCity();
    }

    public function down(): void
    {
        Schema::dropIfExists('teacher_city');
        Schema::dropIfExists('peformer_musician');
        Schema::dropIfExists('peformer_admins');
        Schema::dropIfExists('schools');

        $this->reverseExtendRehearsals();
        $this->reverseExtendStudios();
        $this->reverseExtendPeformers();
        $this->reverseExtendTeachers();
        $this->reverseExtendMusicians();

        // unique(name) restoration skipped — данные могли конфликтовать; при откате вручную.
    }

    private function dropUniqueNameIndexes(): void
    {
        foreach (['musicians', 'teachers', 'peformers', 'studios', 'rehearsals'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            try {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropUnique(['name']);
                });
            } catch (\Throwable) {
                // индекс мог называться иначе или отсутствовать
            }
        }
    }

    private function createSchoolsTable(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable()->unique();
            $table->boolean('public_page_enabled')->default(false);
            $table->json('layout_draft')->nullable();
            $table->json('layout_published')->nullable();
            $table->string('legal_entity_type')->nullable();
            $table->string('company_name')->nullable();
            $table->string('inn')->nullable();
            $table->string('ogrn')->nullable();
            $table->timestamps();
        });
    }

    private function extendMusicians(): void
    {
        Schema::table('musicians', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('public_page_enabled')->default(false)->after('slug');
            $table->json('layout_draft')->nullable();
            $table->json('layout_published')->nullable();
        });

        try {
            Schema::table('musicians', function (Blueprint $table) {
                $table->unique('user_id');
            });
        } catch (\Throwable) {
        }
    }

    private function extendTeachers(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('public_page_enabled')->default(false)->after('slug');
            $table->json('layout_draft')->nullable();
            $table->json('layout_published')->nullable();
            $table->boolean('available_other_cities')->default(false);
            $table->string('legal_entity_type')->nullable();
            $table->string('company_name')->nullable();
            $table->string('inn')->nullable();
            $table->string('ogrn')->nullable();
        });

        try {
            Schema::table('teachers', function (Blueprint $table) {
                $table->unique('user_id');
            });
        } catch (\Throwable) {
        }
    }

    private function extendPeformers(): void
    {
        Schema::table('peformers', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('public_page_enabled')->default(false)->after('slug');
            $table->string('performer_kind')->default('band')->after('public_page_enabled');
            $table->json('layout_draft')->nullable();
            $table->json('layout_published')->nullable();
        });

        Schema::create('peformer_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peformer_id')->constrained('peformers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['peformer_id', 'user_id']);
        });

        Schema::create('peformer_musician', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peformer_id')->constrained('peformers')->cascadeOnDelete();
            $table->foreignId('musician_id')->constrained('musicians')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->boolean('show_on_musician_profile')->default(true);
            $table->foreignId('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['peformer_id', 'musician_id']);
        });
    }

    private function extendStudios(): void
    {
        Schema::table('studios', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('public_page_enabled')->default(false)->after('slug');
            $table->json('layout_draft')->nullable();
            $table->json('layout_published')->nullable();
            $table->string('legal_entity_type')->nullable();
            $table->string('company_name')->nullable();
            $table->string('inn')->nullable();
            $table->string('ogrn')->nullable();
        });
    }

    private function extendRehearsals(): void
    {
        Schema::table('rehearsals', function (Blueprint $table) {
            $table->foreignId('owner_user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            $table->string('slug')->nullable()->unique()->after('name');
            $table->boolean('public_page_enabled')->default(false)->after('slug');
            $table->json('layout_draft')->nullable();
            $table->json('layout_published')->nullable();
            $table->string('legal_entity_type')->nullable();
            $table->string('company_name')->nullable();
            $table->string('inn')->nullable();
            $table->string('ogrn')->nullable();
        });
    }

    private function createTeacherCity(): void
    {
        Schema::create('teacher_city', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['teacher_id', 'city_id']);
        });
    }

    private function reverseExtendMusicians(): void
    {
        if (! Schema::hasTable('musicians')) {
            return;
        }
        Schema::table('musicians', function (Blueprint $table) {
            try {
                $table->dropUnique(['user_id']);
            } catch (\Throwable) {
            }
            $columns = ['slug', 'public_page_enabled', 'layout_draft', 'layout_published'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('musicians', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function reverseExtendTeachers(): void
    {
        if (! Schema::hasTable('teachers')) {
            return;
        }
        Schema::table('teachers', function (Blueprint $table) {
            try {
                $table->dropForeign(['user_id']);
            } catch (\Throwable) {
            }
            try {
                $table->dropUnique(['user_id']);
            } catch (\Throwable) {
            }
            foreach ([
                'user_id', 'slug', 'public_page_enabled', 'layout_draft', 'layout_published',
                'available_other_cities', 'legal_entity_type', 'company_name', 'inn', 'ogrn',
            ] as $col) {
                if (Schema::hasColumn('teachers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function reverseExtendPeformers(): void
    {
        if (Schema::hasTable('peformers')) {
            Schema::table('peformers', function (Blueprint $table) {
                try {
                    $table->dropForeign(['owner_user_id']);
                } catch (\Throwable) {
                }
                foreach ([
                    'owner_user_id', 'slug', 'public_page_enabled', 'performer_kind',
                    'layout_draft', 'layout_published',
                ] as $col) {
                    if (Schema::hasColumn('peformers', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }

    private function reverseExtendStudios(): void
    {
        if (! Schema::hasTable('studios')) {
            return;
        }
        Schema::table('studios', function (Blueprint $table) {
            try {
                $table->dropForeign(['owner_user_id']);
            } catch (\Throwable) {
            }
            foreach ([
                'owner_user_id', 'slug', 'public_page_enabled', 'layout_draft', 'layout_published',
                'legal_entity_type', 'company_name', 'inn', 'ogrn',
            ] as $col) {
                if (Schema::hasColumn('studios', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    private function reverseExtendRehearsals(): void
    {
        if (! Schema::hasTable('rehearsals')) {
            return;
        }
        Schema::table('rehearsals', function (Blueprint $table) {
            try {
                $table->dropForeign(['owner_user_id']);
            } catch (\Throwable) {
            }
            foreach ([
                'owner_user_id', 'slug', 'public_page_enabled', 'layout_draft', 'layout_published',
                'legal_entity_type', 'company_name', 'inn', 'ogrn',
            ] as $col) {
                if (Schema::hasColumn('rehearsals', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};

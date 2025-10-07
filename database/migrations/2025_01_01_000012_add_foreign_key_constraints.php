<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add foreign key constraints only if they don't exist
        Schema::table('members', function (Blueprint $table) {
            if (!$this->foreignKeyExists('members', 'members_family_id_foreign')) {
                $table->foreign('family_id')->references('id')->on('families')->onDelete('set null');
            }
            if (!$this->foreignKeyExists('members', 'members_parent_id_foreign')) {
                $table->foreign('parent_id')->references('id')->on('members')->onDelete('set null');
            }
            if (!$this->foreignKeyExists('members', 'members_godparent_id_foreign')) {
                $table->foreign('godparent_id')->references('id')->on('members')->onDelete('set null');
            }
            if (!$this->foreignKeyExists('members', 'members_minister_id_foreign')) {
                $table->foreign('minister_id')->references('id')->on('members')->onDelete('set null');
            }
        });

        if (Schema::hasTable('sacraments')) {
            Schema::table('sacraments', function (Blueprint $table) {
                if (!$this->foreignKeyExists('sacraments', 'sacraments_member_id_foreign')) {
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
                }
            });
        }

        if (Schema::hasTable('baptism_records')) {
            Schema::table('baptism_records', function (Blueprint $table) {
                if (!$this->foreignKeyExists('baptism_records', 'baptism_records_member_id_foreign')) {
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
                }
            });
        }

        if (Schema::hasTable('marriage_records')) {
            Schema::table('marriage_records', function (Blueprint $table) {
                if (!$this->foreignKeyExists('marriage_records', 'marriage_records_husband_member_id_foreign')) {
                    $table->foreign('husband_member_id')->references('id')->on('members')->onDelete('set null');
                }
                if (!$this->foreignKeyExists('marriage_records', 'marriage_records_wife_member_id_foreign')) {
                    $table->foreign('wife_member_id')->references('id')->on('members')->onDelete('set null');
                }
            });
        }

        if (Schema::hasTable('tithes')) {
            Schema::table('tithes', function (Blueprint $table) {
                if (!$this->foreignKeyExists('tithes', 'tithes_member_id_foreign')) {
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
                }
            });
        }

        if (Schema::hasTable('activity_participants')) {
            Schema::table('activity_participants', function (Blueprint $table) {
                if (!$this->foreignKeyExists('activity_participants', 'activity_participants_activity_id_foreign')) {
                    $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
                }
                if (!$this->foreignKeyExists('activity_participants', 'activity_participants_member_id_foreign')) {
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('set null');
                }
            });
        }

        if (Schema::hasTable('family_relationships')) {
            Schema::table('family_relationships', function (Blueprint $table) {
                if (!$this->foreignKeyExists('family_relationships', 'family_relationships_family_id_foreign')) {
                    $table->foreign('family_id')->references('id')->on('families')->onDelete('cascade');
                }
                if (!$this->foreignKeyExists('family_relationships', 'family_relationships_member_id_foreign')) {
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
                }
            });
        }

        if (Schema::hasTable('group_members')) {
            Schema::table('group_members', function (Blueprint $table) {
                if (!$this->foreignKeyExists('group_members', 'group_members_group_id_foreign')) {
                    $table->foreign('group_id')->references('id')->on('community_groups')->onDelete('cascade');
                }
                if (!$this->foreignKeyExists('group_members', 'group_members_member_id_foreign')) {
                    $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
                }
            });
        }

        // Add performance indexes only if they don't exist
        Schema::table('members', function (Blueprint $table) {
            if (!$this->indexExists('members', 'idx_status_created')) {
                $table->index(['membership_status', 'created_at'], 'idx_status_created');
            }
            if (!$this->indexExists('members', 'idx_church_matrimony')) {
                $table->index(['local_church', 'matrimony_status'], 'idx_church_matrimony');
            }
            if (!$this->indexExists('members', 'idx_contact_info')) {
                $table->index(['phone', 'email'], 'idx_contact_info');
            }
        });

        if (Schema::hasTable('tithes')) {
            Schema::table('tithes', function (Blueprint $table) {
                if (!$this->indexExists('tithes', 'idx_date_amount')) {
                    $table->index(['contribution_date', 'amount'], 'idx_date_amount');
                }
            });
        }

        if (Schema::hasTable('activities')) {
            Schema::table('activities', function (Blueprint $table) {
                if (!$this->indexExists('activities', 'idx_date_status')) {
                    $table->index(['activity_date', 'activity_status'], 'idx_date_status');
                }
            });
        }
    }

    /**
     * Helper method to check if foreign key exists
     */
    private function foreignKeyExists($table, $foreignKey)
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $foreignKeys = $sm->listTableForeignKeys($table);
        
        foreach ($foreignKeys as $key) {
            if ($key->getName() === $foreignKey) {
                return true;
            }
        }
        return false;
    }

    /**
     * Helper method to check if index exists
     */
    private function indexExists($table, $index)
    {
        $sm = Schema::getConnection()->getDoctrineSchemaManager();
        $indexes = $sm->listTableIndexes($table);
        
        return array_key_exists($index, $indexes);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraints
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['godparent_id']);
            $table->dropForeign(['minister_id']);
        });

        Schema::table('sacraments', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('baptism_records', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('marriage_records', function (Blueprint $table) {
            $table->dropForeign(['husband_member_id']);
            $table->dropForeign(['wife_member_id']);
        });

        Schema::table('tithes', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('activity_participants', function (Blueprint $table) {
            $table->dropForeign(['activity_id']);
            $table->dropForeign(['member_id']);
        });

        Schema::table('family_relationships', function (Blueprint $table) {
            $table->dropForeign(['family_id']);
            $table->dropForeign(['member_id']);
        });

        Schema::table('group_members', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropForeign(['member_id']);
        });

        // Drop additional indexes
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex('idx_status_created');
            $table->dropIndex('idx_church_matrimony');
            $table->dropIndex('idx_contact_info');
        });

        Schema::table('tithes', function (Blueprint $table) {
            $table->dropIndex('idx_date_amount');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('idx_date_status');
        });
    }
};

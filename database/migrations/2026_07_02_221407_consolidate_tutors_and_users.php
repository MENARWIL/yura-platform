<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'ci')) {
                $table->string('ci')->nullable()->unique()->after('password');
            }
        });

        // Migrate tutors to users
        if (Schema::hasTable('tutors')) {
            $tutors = DB::table('tutors')->get();
            foreach ($tutors as $tutor) {
                // Check if a user with this CI already exists
                $existingUser = DB::table('users')->where('ci', $tutor->ci)->first();
                if (!$existingUser) {
                    $email = 'tutor-' . $tutor->ci . '@yura.local';
                    // Check if email already exists, just in case
                    $existingByEmail = DB::table('users')->where('email', $email)->first();
                    if ($existingByEmail) {
                        $userId = $existingByEmail->id;
                        DB::table('users')->where('id', $userId)->update([
                            'ci' => $tutor->ci,
                            'telefono' => $tutor->phone,
                        ]);
                    } else {
                        $userId = DB::table('users')->insertGetId([
                            'name' => $tutor->name,
                            'email' => $email,
                            'password' => Hash::make('password123'),
                            'role' => 'tutor',
                            'rol' => 'tutor', // Sync
                            'telefono' => $tutor->phone,
                            'ci' => $tutor->ci,
                            'estado' => 'activo',
                            'created_at' => $tutor->created_at ?? now(),
                            'updated_at' => $tutor->updated_at ?? now(),
                        ]);
                    }
                } else {
                    $userId = $existingUser->id;
                    DB::table('users')->where('id', $userId)->update([
                        'telefono' => $tutor->phone,
                    ]);
                }

                // Get relationships in student_tutor and migrate to student_family_members
                if (Schema::hasTable('student_tutor')) {
                    $relations = DB::table('student_tutor')->where('tutor_id', $tutor->id)->get();
                    foreach ($relations as $relation) {
                        if (Schema::hasTable('student_family_members')) {
                            // Check if relationship exists
                            $exists = DB::table('student_family_members')
                                ->where('student_id', $relation->student_id)
                                ->where('user_id', $userId)
                                ->exists();

                            if (!$exists) {
                                // Normalize relationship type
                                $type = strtolower($relation->relationship);
                                if (!in_array($type, ['padre', 'madre', 'tutor'])) {
                                    $type = 'tutor';
                                }

                                DB::table('student_family_members')->insert([
                                    'student_id' => $relation->student_id,
                                    'user_id' => $userId,
                                    'relation_type' => $type,
                                    'is_primary' => true,
                                    'can_view' => true,
                                    'can_edit' => false,
                                    'can_receive_reports' => true,
                                    'active' => true,
                                    'created_at' => $relation->created_at ?? now(),
                                    'updated_at' => $relation->updated_at ?? now(),
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'ci')) {
                $table->dropColumn('ci');
            }
        });
    }
};

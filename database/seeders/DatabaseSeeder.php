<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Teacher;
use App\Models\Tribute;
use App\Models\User;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->updateOrCreate(
            ['email' => 'superadmin@guruvandan.test'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'phone' => '9000000001',
                'role' => Role::SuperAdmin,
                'is_active' => true,
                'must_change_password' => false,
                'password' => Hash::make('password123'),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'admin@guruvandan.test'],
            [
                'name' => 'School Admin',
                'username' => 'admin',
                'phone' => '9000000002',
                'role' => Role::Admin,
                'is_active' => true,
                'must_change_password' => false,
                'password' => Hash::make('password123'),
            ]
        );

        $realTeacherNames = [
            "Seema Ma'am", "Poonam Ma'am", "Pallavi Ma'am", "Anjana Ma'am", 'Rahul Sir', "Rajmani Ma'am",
            "Jaya Ma'am", 'D. L. Sir', "Parimal Ma'am", "Nivedita Ma'am", "Saziya Ma'am", "Aparna Ma'am",
            "Priya Ma'am", "Ankita Ma'am", 'Manoj Sir', "Jyotsana Ma'am", "Aditi Ma'am", "Sweksha Ma'am",
            "Sita Ma'am", "Rashmi Ma'am", 'Sujay Sir', 'Ajeet Sir', "Anoopa Ma'am", 'D. P. Pandey Sir',
        ];

        Teacher::query()->whereDoesntHave('tributes')->whereDoesntHave('certificate')->whereHas('user', function ($query) use ($realTeacherNames): void {
            $query->where('role', Role::Teacher)->whereNotIn('name', $realTeacherNames);
        })->get()->each(function (Teacher $teacher): void {
            $user = $teacher->user;
            $teacher->delete();
            $user?->delete();
        });

        Teacher::query()->where(function ($query) use ($realTeacherNames): void {
            $query->whereHas('user', fn ($user) => $user->whereNotIn('name', $realTeacherNames));
        })->where(fn ($query) => $query->whereHas('tributes')->orWhereHas('certificate'))->update([
            'is_active' => false,
            'is_public' => false,
            'archived_at' => now(),
        ]);

        $teacherDefinitions = collect([
            ['name' => "Seema Ma'am", 'username' => 'seema'],
            ['name' => "Poonam Ma'am", 'username' => 'poonam'],
            ['name' => "Pallavi Ma'am", 'username' => 'pallavi'],
            ['name' => "Anjana Ma'am", 'username' => 'anjana'],
            ['name' => 'Rahul Sir', 'username' => 'rahul'],
            ['name' => "Rajmani Ma'am", 'username' => 'rajmani'],
            ['name' => "Jaya Ma'am", 'username' => 'jaya'],
            ['name' => 'D. L. Sir', 'username' => 'dl'],
            ['name' => "Parimal Ma'am", 'username' => 'parimal'],
            ['name' => "Nivedita Ma'am", 'username' => 'nivedita'],
            ['name' => "Saziya Ma'am", 'username' => 'saziya', 'location' => '1st Floor'],
            ['name' => "Aparna Ma'am", 'username' => 'aparna'],
            ['name' => "Priya Ma'am", 'username' => 'priya'],
            ['name' => "Ankita Ma'am", 'username' => 'ankita'],
            ['name' => 'Manoj Sir', 'username' => 'manoj'],
            ['name' => "Jyotsana Ma'am", 'username' => 'jyotsana'],
            ['name' => "Aditi Ma'am", 'username' => 'aditi'],
            ['name' => "Sweksha Ma'am", 'username' => 'sweksha'],
            ['name' => "Sita Ma'am", 'username' => 'sita'],
            ['name' => "Rashmi Ma'am", 'username' => 'rashmi'],
            ['name' => 'Sujay Sir', 'username' => 'sujay', 'designation' => 'Director'],
            ['name' => 'Ajeet Sir', 'username' => 'ajeet', 'designation' => 'Principal'],
            ['name' => "Anoopa Ma'am", 'username' => 'anoopa'],
            ['name' => 'D. P. Pandey Sir', 'username' => 'dp-pandey'],
        ])->map(function (array $teacherData, int $index): Teacher {
            $username = strtolower($teacherData['username']);
            $name = $teacherData['name'];
            $user = User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => null,
                    'phone' => null,
                    'role' => Role::Teacher,
                    'is_active' => true,
                    'must_change_password' => true,
                    'password' => Hash::make('teacher123'),
                ]
            );

            return Teacher::query()->updateOrCreate(
                ['slug' => Str::slug(str_replace(["'", '.'], '', $name))],
                [
                    'user_id' => $user->id,
                    'designation' => $teacherData['designation'] ?? 'Teacher',
                    'short_intro' => 'A respected GuruVandan mentor cherished by students for guidance, patience, and encouragement.',
                    'banner_title' => 'Guidance, gratitude, and memories from students.',
                    'qualification' => 'Experienced educator',
                    'years_experience' => 5 + ($index % 18),
                    'joining_year' => 2008 + ($index % 14),
                    'location' => $teacherData['location'] ?? null,
                    'bio' => $name.' is honoured on Guru Purnima for shaping students with care, discipline, and steady encouragement. This digital tribute page preserves messages, memories, poems, and creative wishes from students.',
                    'quote' => 'A teacher lights the path long after the class is over.',
                    'is_active' => true,
                    'is_public' => true,
                    'archived_at' => null,
                ]
            );
        });

        $ritik = User::query()->updateOrCreate(
            ['username' => 'ritik.kushwaha'],
            [
                'name' => 'Ritik Kushwaha',
                'email' => 'ritik.kushwaha@guruvandan.test',
                'phone' => '9000000099',
                'role' => Role::Student,
                'is_active' => true,
                'must_change_password' => false,
                'password' => Hash::make('password'),
            ]
        );

        $ritik->studentProfile()->updateOrCreate(
            ['user_id' => $ritik->id],
            [
                'class_name' => 'Class 10',
                'section' => 'A',
                'roll_number' => '99',
            ]
        );

        $students = User::factory()->count(8)->create()->each(function (User $user, int $index): void {
            $user->update([
                'role' => Role::Student,
                'name' => 'Student '.($index + 1),
                'username' => 'student'.($index + 1),
                'email' => "student{$index}@guruvandan.test",
                'must_change_password' => $index < 2,
                'password' => Hash::make('password'),
            ]);

            $user->studentProfile()->create([
                'class_name' => 'Class '.fake()->numberBetween(8, 12),
                'section' => fake()->randomElement(['A', 'B', 'C']),
                'roll_number' => (string) ($index + 1),
            ]);
        });

        $tributeDefinitions = [
            ['type' => 'thank_you_message', 'language' => 'english', 'title' => 'Thank you for guiding us', 'message' => 'Your patience and encouragement made every classroom day meaningful.', 'status' => 'approved', 'featured' => true],
            ['type' => 'poem', 'language' => 'hinglish', 'title' => 'Guru ka aashirwad', 'message' => 'Aapki muskaan aur guidance ne humein confidence diya.', 'status' => 'approved', 'featured' => true],
            ['type' => 'letter', 'language' => 'english', 'title' => 'A letter of gratitude', 'message' => 'Thank you for believing in us even when we were unsure of ourselves.', 'status' => 'pending', 'featured' => false],
            ['type' => 'drawing', 'language' => 'english', 'title' => 'Classroom memory', 'message' => 'This memory reminds me how warmly you helped every student.', 'status' => 'approved', 'featured' => false],
            ['type' => 'video_wish', 'language' => 'hindi', 'title' => 'Guru Purnima pranam', 'message' => 'Guru Purnima par aapko koti koti pranam.', 'status' => 'rejected', 'featured' => false],
        ];

        foreach ($tributeDefinitions as $index => $tributeData) {
            Tribute::query()->create([
                'student_id' => $students[$index % $students->count()]->id,
                'teacher_id' => $teacherDefinitions[$index % 12]->id,
                'tribute_type' => $tributeData['type'],
                'title' => $tributeData['title'],
                'message' => $tributeData['message'],
                'language' => $tributeData['language'],
                'status' => $tributeData['status'],
                'rejection_reason' => $tributeData['status'] === 'rejected' ? 'Please revise and resubmit with clearer wording.' : null,
                'approved_by' => $superAdmin->id,
                'approved_at' => $tributeData['status'] === 'approved' ? now() : null,
                'is_featured' => $tributeData['featured'],
            ]);
        }

        $ritikMessages = [
            'Your words are like a diya in a quiet room; they make every difficult lesson feel possible. With respect, Ritik Kushwaha.',
            "A good teacher does not only finish the chapter, they open a window in the student's mind. Thank you for opening many for us. With respect, Ritik Kushwaha.",
            'In your class, discipline never felt heavy because it always came with care. That balance is your true blessing to us. With respect, Ritik Kushwaha.',
            'You taught us that marks matter, but courage, honesty, and effort matter even more. That lesson will stay beyond school. With respect, Ritik Kushwaha.',
            'Some teachers explain answers; you taught us how to search for them. That is the gift of a real guru. With respect, Ritik Kushwaha.',
            'Your patience is the bridge between our doubts and our confidence. Thank you for walking that bridge with us every day. With respect, Ritik Kushwaha.',
            'A classroom becomes memorable when a teacher fills it with hope. Your guidance made ordinary days feel meaningful. With respect, Ritik Kushwaha.',
            'You planted confidence quietly, like seeds in soil, and today many students stand taller because of you. With respect, Ritik Kushwaha.',
            'Your teaching has the warmth of encouragement and the strength of truth. That is why students remember your words. With respect, Ritik Kushwaha.',
            "A guru's blessing is not always spoken; sometimes it is hidden in correction, practice, and belief. Thank you for all three. With respect, Ritik Kushwaha.",
            'You made learning feel less like pressure and more like a journey where every step had purpose. With respect, Ritik Kushwaha.',
            'Whenever we felt unsure, your faith in us became our borrowed strength until we found our own. With respect, Ritik Kushwaha.',
            'Your lessons are not limited to notebooks; they have become small lights we carry into life. With respect, Ritik Kushwaha.',
            "The best teachers leave echoes of kindness in a student's memory. Your kindness will always be one of those echoes. With respect, Ritik Kushwaha.",
            'You turned mistakes into practice and practice into progress. That simple magic changed how we look at learning. With respect, Ritik Kushwaha.',
            "A teacher's smile can make a student try one more time. Thank you for giving us that courage again and again. With respect, Ritik Kushwaha.",
            'You showed us that knowledge becomes powerful only when it is joined with humility and effort. With respect, Ritik Kushwaha.',
            'In every explanation, there was patience; in every correction, there was care; in every class, there was purpose. With respect, Ritik Kushwaha.',
            'You are one of those teachers whose guidance becomes a quiet voice in the mind during difficult moments. With respect, Ritik Kushwaha.',
            'Thank you for making students feel seen, heard, and capable. That feeling is a gift greater than any prize. With respect, Ritik Kushwaha.',
            'Leadership becomes inspiring when it is rooted in service. Your example teaches us to aim high and stay grounded. With respect, Ritik Kushwaha.',
            "A principal's guidance shapes not only classrooms but the spirit of the whole school. Thank you for leading with vision. With respect, Ritik Kushwaha.",
            'Your presence brings calm, clarity, and confidence to the learning journey. Thank you for being a memorable guru. With respect, Ritik Kushwaha.',
            'Every school needs teachers who turn knowledge into inspiration. Your guidance is one of the reasons students believe they can grow. With respect, Ritik Kushwaha.',
        ];

        foreach ($teacherDefinitions as $index => $teacher) {
            Tribute::query()->updateOrCreate(
                [
                    'student_id' => $ritik->id,
                    'teacher_id' => $teacher->id,
                    'title' => 'Guru Purnima thought from Ritik',
                ],
                [
                    'tribute_type' => 'poem',
                    'message' => $ritikMessages[$index % count($ritikMessages)],
                    'language' => 'english',
                    'status' => 'approved',
                    'rejection_reason' => null,
                    'approved_by' => $superAdmin->id,
                    'approved_at' => now(),
                    'is_featured' => $index < 6,
                ]
            );
        }

        $event = Event::query()->updateOrCreate(
            ['is_active' => true],
            [
                'title' => 'Guru Purnima Celebration 2026',
                'description' => 'A school celebration of gratitude, respect, and Digital Guru Dakshina for every teacher.',
                'event_date' => '2026-07-29',
                'event_time' => '09:00',
                'venue' => 'School',
                'chief_guest' => null,
                'livestream_url' => null,
            ]
        );

        $event->schedules()->delete();
        foreach ([
            ['09:00', 'Guru Vandana and Prayer', 'A respectful opening dedicated to teachers and the spirit of Guru Purnima.'],
            ['09:30', 'GuruVandan Tribute Reveal', 'Digital messages, poems, memories, and creative wishes are revealed for teachers.'],
            ['10:00', 'Teacher Felicitation', 'Students and school leaders honour the gurus who guide the learning journey.'],
            ['11:00', 'Student Gratitude Performances', 'Poetry, speeches, music, and short wishes expressing thanks to teachers.'],
        ] as $index => $item) {
            $event->schedules()->create(['start_time' => $item[0], 'title' => $item[1], 'detail' => $item[2], 'sort_order' => $index, 'is_enabled' => true]);
        }

        $settings = app(SettingsService::class);
        foreach ([
            'platform_name' => 'GuruVandan',
            'platform_tagline' => 'Honour the teachers who shaped your journey.',
            'school_name' => 'SAVVY MOTHER INTERNATIONAL SCHOOL',
            'principal_name' => 'Ajeet Sir',
            'school_email' => 'office@shantiniketan.example',
            'school_phone' => '+91 90000 00000',
            'school_address' => 'Knowledge Avenue, New Delhi',
            'event_title' => $event->title,
            'event_date' => $event->event_date->format('Y-m-d'),
            'event_time' => '09:00',
            'event_venue' => $event->venue,
            'chief_guest' => $event->chief_guest,
            'celebration_message' => $event->description,
            'certificate_title' => 'Certificate of Appreciation',
            'certificate_text' => 'With gratitude for guiding, inspiring, and shaping the lives of students.',
            'certificate_footer' => 'Presented with gratitude on Guru Purnima',
            'certificate_signature_label' => 'Principal',
            'certificate_template' => 'classic',
            'ai_enabled' => true,
            'ai_rate_limit' => 10,
            'ai_fallback_enabled' => true,
            'upload_image_kb' => 5120,
            'upload_audio_kb' => 12288,
            'upload_video_kb' => 51200,
            'upload_allowed_types' => ['jpg', 'jpeg', 'png', 'webp', 'mp3', 'wav', 'm4a', 'mp4', 'webm'],
            'reveal_enabled' => true,
            'reveal_at' => '2026-07-29 09:30:00',
        ] as $key => $value) {
            $settings->put($key, $value);
        }

        Teacher::query()->each(function (Teacher $teacher): void {
            Certificate::query()->firstOrCreate(
                ['teacher_id' => $teacher->id],
                ['certificate_number' => 'GV-2026-'.strtoupper(Str::random(16)), 'verification_token' => Str::random(48), 'generated_at' => now()]
            );
        });
    }
}

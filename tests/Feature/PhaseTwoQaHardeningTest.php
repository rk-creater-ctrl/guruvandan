<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Certificate;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\Tribute;
use App\Models\User;
use App\Services\AI\GeminiAiProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseTwoQaHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'platform_name' => 'GuruVandan',
            'platform_tagline' => 'Honour the teachers who shaped your journey.',
            'school_name' => 'Shantiniketan Senior Secondary School',
            'reveal_enabled' => '0',
            'upload_image_kb' => '5120',
            'upload_audio_kb' => '12288',
            'upload_video_kb' => '51200',
        ] as $key => $value) {
            Setting::query()->create(compact('key', 'value'));
        }
    }

    public function test_student_admin_reveal_public_workflow_renders_end_to_end(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();
        $admin = $this->user(Role::Admin, 'admin@example.com');

        $this->actingAs($student)->post(route('student.tributes.store'), [
            'teacher_id' => $teacher->id,
            'tribute_type' => 'thank_you_message',
            'title' => 'Release QA Tribute',
            'message' => 'Thank you for helping me love science.',
            'language' => 'english',
        ])->assertSessionHas('status');

        $tribute = Tribute::query()->where('title', 'Release QA Tribute')->firstOrFail();
        $this->get(route('teachers.show', $teacher))->assertDontSee('Release QA Tribute');

        $this->actingAs($admin)
            ->patch(route('admin.tributes.moderate', $tribute), ['status' => 'approved'])
            ->assertSessionHas('status');

        Setting::query()->where('key', 'reveal_enabled')->update(['value' => '1']);

        $this->get(route('teachers.show', $teacher))
            ->assertOk()
            ->assertSee('Release QA Tribute');
        $this->get(route('wall'))
            ->assertOk()
            ->assertSee('Release QA Tribute');
    }

    public function test_unsafe_upload_names_and_empty_files_are_rejected_without_storage(): void
    {
        Storage::fake('local');
        [$student, $teacher] = $this->studentAndTeacher();

        foreach ([
            UploadedFile::fake()->create('payload.php.jpg', 1, 'image/jpeg'),
            UploadedFile::fake()->create('empty.png', 0, 'image/png'),
            UploadedFile::fake()->create('../memory.jpg', 1, 'image/jpeg'),
        ] as $file) {
            $this->actingAs($student)->post(route('student.tributes.store'), [
                'teacher_id' => $teacher->id,
                'tribute_type' => 'photo_memory',
                'title' => 'Unsafe upload check',
                'message' => 'Testing bad upload.',
                'language' => 'english',
                'media' => $file,
            ])->assertSessionHasErrors('media');
        }

        $this->assertSame(0, Tribute::query()->count());
        $this->assertCount(0, Storage::disk('local')->allFiles());
    }

    public function test_inactive_teacher_qr_and_missing_pages_are_safe(): void
    {
        $teacher = $this->teacher('inactive-teacher@example.com', 'inactive-teacher');
        $teacher->update(['is_active' => false]);

        $this->get(route('teachers.qr.download', $teacher))->assertNotFound();
        $this->get(route('teachers.qr.print', $teacher))->assertNotFound();
        $this->get('/teachers/not-a-real-teacher')->assertNotFound()->assertSee('We could not find that page.');
        $this->actingAs($this->student())->get(route('admin.dashboard'))->assertForbidden()->assertSee('This area is protected.');
    }

    public function test_teacher_certificate_download_and_qr_verification_workflow(): void
    {
        $teacher = $this->teacher();
        $certificate = Certificate::query()->create([
            'teacher_id' => $teacher->id,
            'certificate_number' => 'GV-2026-QA-CERTIFICATE',
            'verification_token' => str_repeat('z', 48),
            'generated_at' => now(),
        ]);

        $this->actingAs($teacher->user)
            ->get(route('teacher.certificate.download'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->actingAs($this->user(Role::Admin, 'cert-admin@example.com'))
            ->get(route('admin.certificates.qr', $certificate))
            ->assertOk()
            ->assertHeader('content-type', 'image/svg+xml')
            ->assertSee('<svg', false);

        $this->get(route('certificates.verify', $certificate->verification_token))->assertOk()->assertSee('Valid');
    }

    public function test_gemini_provider_keeps_api_key_out_of_the_url(): void
    {
        config()->set('services.gemini.api_key', 'secret-test-key');
        config()->set('services.gemini.model', 'gemini-test-model');

        Http::fake(function ($request) {
            $this->assertStringNotContainsString('secret-test-key', (string) $request->url());
            $this->assertSame('secret-test-key', $request->header('x-goog-api-key')[0] ?? null);

            return Http::response([
                'candidates' => [
                    ['content' => ['parts' => [['text' => 'A safe generated message.']]]],
                ],
            ]);
        });

        $content = (new GeminiAiProvider)->generate([
            'content_type_label' => 'Thank-you message',
            'language_label' => 'English',
            'teacher_name' => 'Teacher One',
            'experience' => 'A classroom memory',
            'desired_length' => 'short',
        ]);

        $this->assertSame('A safe generated message.', $content);
    }

    public function test_role_specific_username_login_portals_are_enforced(): void
    {
        $teacher = $this->teacher();
        $teacher->user->update(['username' => 'mentor-login', 'password' => 'Password123!', 'must_change_password' => false]);
        $student = $this->student('student-login@example.com');
        $student->update(['username' => 'student-login', 'password' => 'Password123!', 'must_change_password' => false]);

        $this->post(route('teacher.login.submit'), ['username' => 'mentor-login', 'password' => 'Password123!'])
            ->assertRedirect(route('dashboard'));
        auth()->logout();

        $this->post(route('student.login.submit'), ['username' => 'mentor-login', 'password' => 'Password123!'])
            ->assertSessionHasErrors('username');
        $this->post(route('teacher.login.submit'), ['username' => 'student-login', 'password' => 'Password123!'])
            ->assertSessionHasErrors('username');
    }

    public function test_super_admin_can_create_teacher_account_and_admin_cannot_change_username(): void
    {
        $super = $this->user(Role::SuperAdmin, 'teacher-super@example.com');
        $admin = $this->user(Role::Admin, 'teacher-admin@example.com');

        $this->actingAs($super)->post(route('admin.teachers.store'), [
            'name' => 'New Guru',
            'username' => 'new.guru',
            'slug' => 'new-guru',
            'designation' => 'Teacher',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'must_change_password' => '1',
            'is_active' => '1',
            'is_public' => '1',
        ])->assertRedirect();

        $teacher = Teacher::query()->where('slug', 'new-guru')->firstOrFail();
        $this->assertSame('new.guru', $teacher->user->username);
        $this->assertTrue($teacher->user->must_change_password);

        $this->actingAs($admin)->put(route('admin.teachers.update', $teacher), [
            'name' => 'New Guru',
            'username' => 'changed-by-admin',
            'slug' => 'new-guru',
            'designation' => 'Teacher',
            'is_active' => '1',
            'is_public' => '1',
        ])->assertSessionHasErrors('username');
    }

    public function test_teacher_can_edit_own_public_profile_without_exposing_username(): void
    {
        $teacher = $this->teacher();
        $teacher->user->update(['username' => 'private-mentor']);

        $this->actingAs($teacher->user)->put(route('teacher.profile.update'), [
            'name' => 'Public Mentor Name',
            'location' => 'North Wing',
            'bio' => 'A refreshed public biography.',
            'quote' => 'Every learner matters.',
        ])->assertSessionHas('status');

        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'location' => 'North Wing', 'qualification' => null]);
        $this->get(route('teachers.show', $teacher->fresh()))
            ->assertOk()
            ->assertSee('Public Mentor Name')
            ->assertDontSee('private-mentor');
    }

    public function test_teacher_can_change_password_from_profile_with_current_password(): void
    {
        $teacher = $this->teacher();
        $teacher->user->update([
            'password' => 'OldPassword123!',
            'must_change_password' => true,
        ]);

        $this->actingAs($teacher->user)->patch(route('teacher.profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHasErrors('current_password');

        $this->actingAs($teacher->user)->patch(route('teacher.profile.password'), [
            'current_password' => 'OldPassword123!',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertSessionHas('status');

        $teacher->user->refresh();
        $this->assertFalse($teacher->user->must_change_password);
        $this->assertTrue(Hash::check('NewPassword123!', $teacher->user->password));
    }

    public function test_duplicate_usernames_are_rejected_case_insensitively(): void
    {
        $super = $this->user(Role::SuperAdmin, 'duplicate-super@example.com');
        $this->teacher()->user->update(['username' => 'seema']);

        $this->actingAs($super)->post(route('admin.teachers.store'), [
            'name' => 'Duplicate Teacher',
            'username' => 'SEEMA',
            'slug' => 'duplicate-teacher',
            'designation' => 'Teacher',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'is_active' => '1',
            'is_public' => '1',
        ])->assertSessionHasErrors('username');
    }

    private function user(Role $role, string $email): User
    {
        return User::factory()->create(['role' => $role, 'email' => $email, 'is_active' => true, 'must_change_password' => false]);
    }

    private function student(string $email = 'student@example.com'): User
    {
        $student = $this->user(Role::Student, $email);
        $student->studentProfile()->create(['class_name' => 'Class 10', 'section' => 'A']);

        return $student;
    }

    private function teacher(string $email = 'teacher@example.com', string $slug = 'teacher-one'): Teacher
    {
        $user = $this->user(Role::Teacher, $email);

        return Teacher::query()->create([
            'user_id' => $user->id,
            'slug' => $slug,
            'designation' => 'Mentor',
            'banner_title' => 'A beloved teacher',
            'bio' => 'A thoughtful biography.',
            'quote' => 'Keep learning.',
            'is_active' => true,
        ]);
    }

    private function studentAndTeacher(): array
    {
        return [$this->student(), $this->teacher()];
    }
}

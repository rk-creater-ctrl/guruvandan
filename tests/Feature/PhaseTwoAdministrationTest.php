<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Certificate;
use App\Models\Event;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\Tribute;
use App\Models\User;
use App\Services\AI\AiMessageGenerator;
use App\Services\AI\AiProvider;
use App\Services\RevealService;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseTwoAdministrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'platform_name' => 'GuruVandan',
            'reveal_enabled' => '0',
            'ai_enabled' => '1',
            'ai_rate_limit' => '10',
            'ai_fallback_enabled' => '1',
            'upload_image_kb' => '5120',
            'upload_audio_kb' => '12288',
            'upload_video_kb' => '51200',
        ] as $key => $value) {
            Setting::query()->create(compact('key', 'value'));
        }
    }

    public function test_super_admin_can_manage_admin_accounts_and_admin_cannot(): void
    {
        $super = $this->user(Role::SuperAdmin);
        $admin = $this->user(Role::Admin);

        $this->actingAs($super)->post(route('super-admin.admins.store'), [
            'name' => 'New Admin',
            'email' => 'new-admin@example.com',
            'role' => 'admin',
            'is_active' => '1',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect();

        $account = User::query()->where('email', 'new-admin@example.com')->firstOrFail();
        $this->actingAs($super)->patch(route('super-admin.admins.password', $account), [
            'password' => 'Replacement123!',
            'password_confirmation' => 'Replacement123!',
        ])->assertSessionHas('status');
        $this->actingAs($admin)->get(route('super-admin.admins'))->assertForbidden();
        $this->assertDatabaseHas('activity_logs', ['action' => 'admin_account_created', 'subject_id' => $account->id]);
    }

    public function test_super_admin_cannot_deactivate_or_delete_own_account(): void
    {
        $super = $this->user(Role::SuperAdmin);
        $this->actingAs($super)->patch(route('super-admin.admins.toggle', $super))->assertStatus(422);
        $this->actingAs($super)->delete(route('super-admin.admins.destroy', $super))->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $super->id, 'is_active' => true]);
    }

    public function test_admin_can_create_update_and_archive_teacher_safely(): void
    {
        $super = $this->user(Role::SuperAdmin);
        $this->actingAs($super)->post(route('admin.teachers.store'), [
            'name' => 'Teacher Managed',
            'username' => 'managed-teacher',
            'email' => 'managed-teacher@example.com',
            'slug' => 'teacher-managed',
            'designation' => 'Mentor',
            'qualification' => 'M.Sc.',
            'years_experience' => 12,
            'is_active' => '1',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ])->assertRedirect();

        $teacher = Teacher::query()->where('slug', 'teacher-managed')->firstOrFail();
        $this->actingAs($super)->put(route('admin.teachers.update', $teacher), [
            'name' => 'Teacher Managed',
            'username' => 'managed-teacher',
            'email' => 'managed-teacher@example.com',
            'slug' => 'teacher-managed',
            'designation' => 'Senior Mentor',
            'is_active' => '1',
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'designation' => 'Senior Mentor']);

        [$student] = $this->studentAndTeacher($teacher);
        Tribute::query()->create($this->tributeData($student, $teacher));
        $this->actingAs($super)->delete(route('admin.teachers.delete', $teacher))->assertSessionHas('status');
        $this->assertDatabaseHas('teachers', ['id' => $teacher->id, 'is_active' => false]);
    }

    public function test_student_management_permissions_counts_and_csv_export(): void
    {
        $admin = $this->user(Role::Admin);
        $student = $this->student();
        $teacher = $this->teacher();
        Tribute::query()->create([...$this->tributeData($student, $teacher), 'status' => 'approved']);

        $this->actingAs($admin)->get(route('admin.students'))->assertOk()->assertSee($student->name);
        $this->actingAs($admin)->get(route('admin.students.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($student)->get(route('admin.students'))->assertForbidden();
        $this->actingAs($admin)->delete(route('admin.students.delete', $student))->assertSessionHas('status');
        $this->assertDatabaseHas('users', ['id' => $student->id, 'is_active' => false]);
    }

    public function test_rejected_tribute_resubmission_preserves_reason_and_logs_history(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();
        $tribute = Tribute::query()->create([
            ...$this->tributeData($student, $teacher),
            'status' => 'rejected',
            'rejection_reason' => 'Please make the wording more respectful.',
        ]);

        $this->actingAs($student)->put(route('student.tributes.resubmit', $tribute), [
            'teacher_id' => $teacher->id,
            'tribute_type' => 'thank_you_message',
            'language' => 'english',
            'title' => 'Corrected tribute',
            'message' => 'Thank you for your patient guidance.',
        ])->assertSessionHas('status');

        $tribute->refresh();
        $this->assertSame('pending', $tribute->status->value);
        $this->assertSame('Please make the wording more respectful.', $tribute->rejection_reason);
        $this->assertNotNull($tribute->resubmitted_at);
        $this->assertDatabaseHas('activity_logs', ['action' => 'tribute_resubmitted', 'subject_id' => $tribute->id]);
    }

    public function test_bulk_moderation_requires_reason_and_records_each_action(): void
    {
        $admin = $this->user(Role::Admin);
        [$student, $teacher] = $this->studentAndTeacher();
        $one = Tribute::query()->create($this->tributeData($student, $teacher));
        $two = Tribute::query()->create([...$this->tributeData($student, $teacher), 'title' => 'Second']);

        $this->actingAs($admin)->post(route('admin.tributes.bulk'), [
            'tribute_ids' => [$one->id, $two->id],
            'action' => 'reject',
        ])->assertSessionHasErrors('rejection_reason');
        $this->actingAs($admin)->post(route('admin.tributes.bulk'), [
            'tribute_ids' => [$one->id, $two->id],
            'action' => 'approve',
        ])->assertSessionHas('status');
        $this->assertSame(2, Tribute::query()->where('status', 'approved')->count());
        $this->assertSame(2, ActivityLog::query()->where('action', 'tribute_bulk_approved')->count());
    }

    public function test_private_media_requires_owner_moderator_or_revealed_approval(): void
    {
        Storage::fake('local');
        [$student, $teacher] = $this->studentAndTeacher();
        $other = $this->student('other-student@example.com');
        $admin = $this->user(Role::Admin);
        $tribute = Tribute::query()->create($this->tributeData($student, $teacher));
        Storage::disk('local')->put("tributes/{$tribute->id}/memory.jpg", 'image-data');
        $media = $tribute->media()->create([
            'media_type' => 'image',
            'disk' => 'local',
            'path' => "tributes/{$tribute->id}/memory.jpg",
            'original_name' => 'memory.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 10,
        ]);

        $this->get(route('media.show', $media))->assertForbidden();
        $this->actingAs($other)->get(route('media.show', $media))->assertForbidden();
        $this->actingAs($student)->get(route('media.show', $media))->assertOk();
        $this->actingAs($admin)->get(route('media.show', $media))->assertOk();

        $tribute->update(['status' => 'approved']);
        Setting::query()->where('key', 'reveal_enabled')->update(['value' => '1']);
        $this->app->forgetInstance(SettingsService::class);
        $this->app->forgetInstance(RevealService::class);
        auth()->logout();
        $this->get(route('media.show', $media))->assertOk();
    }

    public function test_svg_and_executable_uploads_are_rejected(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();
        foreach ([
            UploadedFile::fake()->create('drawing.svg', 5, 'image/svg+xml'),
            UploadedFile::fake()->create('payload.php', 5, 'application/x-php'),
        ] as $file) {
            $this->actingAs($student)->post(route('student.tributes.store'), [
                'teacher_id' => $teacher->id,
                'tribute_type' => 'drawing',
                'title' => 'Unsafe upload',
                'language' => 'english',
                'media' => $file,
            ])->assertSessionHasErrors('media');
        }
    }

    public function test_valid_image_upload_is_stored_privately(): void
    {
        Storage::fake('local');
        [$student, $teacher] = $this->studentAndTeacher();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        $this->actingAs($student)->post(route('student.tributes.store'), [
            'teacher_id' => $teacher->id,
            'tribute_type' => 'drawing',
            'title' => 'My drawing',
            'message' => 'A classroom memory.',
            'language' => 'english',
            'media' => UploadedFile::fake()->createWithContent('drawing.png', $png),
        ])->assertSessionHas('status');

        $media = Tribute::query()->firstOrFail()->media()->firstOrFail();
        $this->assertSame('local', $media->disk);
        Storage::disk('local')->assertExists($media->path);
    }

    public function test_certificate_revocation_regeneration_and_qr_verification(): void
    {
        $admin = $this->user(Role::Admin);
        $teacher = $this->teacher();
        $certificate = Certificate::query()->create([
            'teacher_id' => $teacher->id,
            'certificate_number' => 'GV-2026-SECURECERTIFICATE',
            'verification_token' => str_repeat('v', 48),
            'generated_at' => now(),
        ]);

        $this->actingAs($admin)->patch(route('admin.certificates.revoke', $certificate), ['reason' => 'Issued in error'])->assertSessionHas('status');
        $this->get(route('certificates.verify', $certificate->verification_token))->assertOk()->assertSee('Revoked');
        $this->get(route('certificates.verify', $certificate->certificate_number))->assertOk()->assertSee('GV-2026-SECURECERTIFICATE');
        $token = $certificate->verification_token;
        $this->actingAs($admin)->post(route('admin.certificates.regenerate', $teacher))->assertSessionHas('status');
        $this->assertSame($token, $certificate->fresh()->verification_token);
        $this->actingAs($admin)->get(route('admin.certificates.qr', $certificate))->assertOk()->assertHeader('content-type', 'image/svg+xml');
        $this->get(route('certificates.verify', 'GV-INVALID-CERTIFICATE'))->assertNotFound();
    }

    public function test_public_event_schedule_uses_order_and_hides_disabled_items(): void
    {
        $event = Event::query()->create([
            'title' => 'Celebration',
            'event_date' => now()->addDay()->format('Y-m-d'),
            'event_time' => '09:00',
            'is_active' => true,
        ]);
        $event->schedules()->create(['start_time' => '10:00', 'title' => 'Second item', 'sort_order' => 2, 'is_enabled' => true]);
        $event->schedules()->create(['start_time' => '09:00', 'title' => 'First item', 'sort_order' => 1, 'is_enabled' => true]);
        $event->schedules()->create(['start_time' => '08:00', 'title' => 'Hidden item', 'sort_order' => 0, 'is_enabled' => false]);

        $this->get(route('event'))->assertOk()->assertSeeInOrder(['First item', 'Second item'])->assertDontSee('Hidden item');
    }

    public function test_platform_settings_are_super_admin_only(): void
    {
        $this->actingAs($this->user(Role::Admin))->get(route('super-admin.settings'))->assertForbidden();
        $this->actingAs($this->user(Role::SuperAdmin))->get(route('super-admin.settings'))->assertOk();
    }

    public function test_ai_provider_is_mocked_and_endpoint_is_rate_limited(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();
        Setting::query()->where('key', 'ai_rate_limit')->update(['value' => '1']);
        $this->app->forgetInstance(SettingsService::class);
        $this->app->forgetInstance(AiMessageGenerator::class);
        $this->app->instance(AiProvider::class, new class implements AiProvider
        {
            public function generate(array $payload): string
            {
                return 'A mocked and heartfelt Guru Purnima message.';
            }
        });
        RateLimiter::clear('ai:'.$student->id);
        $payload = [
            'teacher_id' => $teacher->id,
            'teacher_name' => 'Ignored client value',
            'experience' => 'The day my teacher encouraged my presentation.',
            'language' => 'english',
            'content_type' => 'guru_purnima_wish',
            'desired_length' => 'short',
        ];

        $this->actingAs($student)->postJson(route('student.ai.generate'), $payload)->assertOk()->assertJson(['content' => 'A mocked and heartfelt Guru Purnima message.']);
        $this->actingAs($student)->postJson(route('student.ai.generate'), $payload)->assertStatus(429);
    }

    public function test_disabled_account_cannot_login_or_keep_using_role_routes(): void
    {
        $student = User::factory()->create([
            'role' => Role::Student,
            'email' => 'disabled@example.com',
            'password' => 'Password123!',
            'is_active' => false,
        ]);
        $student->studentProfile()->create(['class_name' => 'Class 10']);

        $this->post('/login', ['email' => 'disabled@example.com', 'password' => 'Password123!'])->assertSessionHasErrors('email');
        $this->actingAs($student)->get(route('student.dashboard'))->assertForbidden();
    }

    public function test_teacher_cannot_read_another_teachers_private_media(): void
    {
        Storage::fake('local');
        $ownerTeacher = $this->teacher('owner-teacher@example.com', 'owner-teacher');
        $otherTeacher = $this->teacher('other-teacher@example.com', 'other-teacher');
        $student = $this->student();
        $tribute = Tribute::query()->create([...$this->tributeData($student, $ownerTeacher), 'status' => 'approved']);
        Storage::disk('local')->put('tributes/private/audio.mp3', 'audio');
        $media = $tribute->media()->create(['media_type' => 'audio', 'disk' => 'local', 'path' => 'tributes/private/audio.mp3', 'original_name' => 'audio.mp3', 'mime_type' => 'audio/mpeg', 'size' => 5]);

        $this->actingAs($otherTeacher->user)->get(route('media.show', $media))->assertForbidden();
        $this->actingAs($ownerTeacher->user)->get(route('media.show', $media))->assertForbidden();
        Setting::query()->where('key', 'reveal_enabled')->update(['value' => '1']);
        $this->app->forgetInstance(SettingsService::class);
        $this->app->forgetInstance(RevealService::class);
        $this->actingAs($ownerTeacher->user)->get(route('media.show', $media))->assertOk();
    }

    public function test_primary_pages_render_for_every_role_and_pdf_qr_are_generated(): void
    {
        $event = Event::query()->create([
            'title' => 'Guru Purnima Celebration',
            'event_date' => now()->addMonth()->format('Y-m-d'),
            'event_time' => '09:00',
            'venue' => 'Auditorium',
            'is_active' => true,
        ]);
        $event->schedules()->create(['start_time' => '09:00', 'title' => 'Welcome', 'sort_order' => 0, 'is_enabled' => true]);
        [$student, $teacher] = $this->studentAndTeacher();
        $admin = $this->user(Role::Admin);
        $super = $this->user(Role::SuperAdmin);
        Certificate::query()->create([
            'teacher_id' => $teacher->id,
            'certificate_number' => 'GV-2026-SMOKECERTIFICATE',
            'verification_token' => str_repeat('q', 48),
            'generated_at' => now(),
        ]);

        foreach ([route('home'), route('teachers.index'), route('teachers.show', $teacher), route('wall'), route('event'), route('login')] as $url) {
            $this->get($url)->assertOk();
        }
        $this->actingAs($student)->get(route('student.dashboard'))->assertOk();
        $this->actingAs($teacher->user)->get(route('teacher.dashboard'))->assertOk();
        foreach ([route('admin.dashboard'), route('admin.tributes'), route('admin.teachers'), route('admin.students'), route('admin.event'), route('admin.certificates'), route('admin.activity-logs')] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($super)->get(route('super-admin.admins'))->assertOk();
        $this->actingAs($super)->get(route('super-admin.settings'))->assertOk();
        $this->actingAs($admin)->get(route('admin.certificates.preview', $teacher))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('teachers.qr.download', $teacher))->assertOk()->assertHeader('content-type', 'image/svg+xml');
    }

    private function user(Role $role, ?string $email = null): User
    {
        return User::factory()->create(['role' => $role, 'email' => $email ?? fake()->unique()->safeEmail(), 'is_active' => true]);
    }

    private function student(string $email = 'student@example.com'): User
    {
        $student = $this->user(Role::Student, $email);
        $student->studentProfile()->create(['class_name' => 'Class 10', 'section' => 'A', 'roll_number' => fake()->unique()->numerify('##')]);

        return $student;
    }

    private function teacher(string $email = 'teacher@example.com', string $slug = 'teacher-one'): Teacher
    {
        $user = $this->user(Role::Teacher, $email);

        return Teacher::query()->create(['user_id' => $user->id, 'slug' => $slug, 'designation' => 'Mentor', 'is_active' => true, 'is_public' => true]);
    }

    private function studentAndTeacher(?Teacher $teacher = null): array
    {
        return [$this->student(), $teacher ?? $this->teacher()];
    }

    private function tributeData(User $student, Teacher $teacher): array
    {
        return [
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'tribute_type' => 'thank_you_message',
            'title' => 'A sincere thank you',
            'message' => 'Thank you for guiding me.',
            'language' => 'english',
            'status' => 'pending',
        ];
    }
}

<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Certificate;
use App\Models\Setting;
use App\Models\Teacher;
use App\Models\Tribute;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GuruVandanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::query()->create(['key' => 'platform_name', 'value' => 'GuruVandan']);
        Setting::query()->create(['key' => 'reveal_enabled', 'value' => '0']);
    }

    public function test_student_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Student One',
            'username' => 'student-one',
            'email' => 'student@example.com',
            'phone' => '9999999999',
            'class_name' => 'Class 10 A',
            'section' => 'A',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertRedirect(route('student.dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'student@example.com', 'role' => Role::Student->value]);
    }

    public function test_role_restrictions_block_student_from_admin_dashboard(): void
    {
        $student = User::factory()->create(['role' => Role::Student]);
        $student->studentProfile()->create(['class_name' => 'Class 10 A']);

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_student_can_submit_tribute(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();

        $this->actingAs($student)
            ->post(route('student.tributes.store'), [
                'teacher_id' => $teacher->id,
                'tribute_type' => 'thank_you_message',
                'title' => 'Thank you',
                'message' => 'You inspired me a lot.',
                'language' => 'english',
            ])->assertSessionHas('status');

        $this->assertDatabaseHas('tributes', ['title' => 'Thank you', 'student_id' => $student->id]);
    }

    public function test_student_can_only_delete_own_pending_tribute(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();
        $otherStudent = User::factory()->create(['role' => Role::Student]);
        $otherStudent->studentProfile()->create(['class_name' => 'Class 9 A']);

        $tribute = Tribute::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'tribute_type' => 'poem',
            'title' => 'Pending poem',
            'language' => 'english',
            'status' => 'pending',
        ]);

        $this->actingAs($otherStudent)
            ->delete(route('student.tributes.destroy', $tribute))
            ->assertForbidden();
    }

    public function test_admin_can_approve_tribute(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();
        $admin = User::factory()->create(['role' => Role::Admin]);

        $tribute = Tribute::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'tribute_type' => 'letter',
            'title' => 'Pending letter',
            'language' => 'english',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.tributes.moderate', $tribute), ['status' => 'approved'])
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tributes', ['id' => $tribute->id, 'status' => 'approved']);
    }

    public function test_reveal_mode_hides_public_teacher_wall(): void
    {
        [, $teacher] = $this->studentAndTeacher();

        $this->get(route('teachers.show', $teacher))
            ->assertSee('Tributes will be revealed on Guru Purnima.');
    }

    public function test_memory_wall_shows_only_approved_tributes(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();
        Setting::query()->where('key', 'reveal_enabled')->update(['value' => '1']);

        Tribute::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'tribute_type' => 'poem',
            'title' => 'Visible tribute',
            'language' => 'english',
            'status' => 'approved',
        ]);

        Tribute::query()->create([
            'student_id' => $student->id,
            'teacher_id' => $teacher->id,
            'tribute_type' => 'poem',
            'title' => 'Hidden tribute',
            'language' => 'english',
            'status' => 'pending',
        ]);

        $this->get(route('wall'))
            ->assertSee('Visible tribute')
            ->assertDontSee('Hidden tribute');
    }

    public function test_certificate_verification_page_loads(): void
    {
        [, $teacher] = $this->studentAndTeacher();
        $certificate = Certificate::query()->create([
            'teacher_id' => $teacher->id,
            'certificate_number' => 'GV-TEST-0001',
            'verification_token' => 'verify-token',
            'generated_at' => now(),
        ]);

        $this->get(route('certificates.verify', $certificate->verification_token))
            ->assertOk()
            ->assertSee($teacher->user->name);
    }

    public function test_unsafe_file_is_rejected(): void
    {
        [$student, $teacher] = $this->studentAndTeacher();

        $this->actingAs($student)
            ->post(route('student.tributes.store'), [
                'teacher_id' => $teacher->id,
                'tribute_type' => 'photo_memory',
                'title' => 'Bad file',
                'language' => 'english',
                'media' => UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream'),
            ])->assertSessionHasErrors('media');
    }

    public function test_ai_endpoint_requires_student_auth_and_validation(): void
    {
        $this->post(route('student.ai.generate'), [])->assertRedirect(route('login'));

        $student = User::factory()->create(['role' => Role::Student]);
        $student->studentProfile()->create(['class_name' => 'Class 10 A']);

        $this->actingAs($student)
            ->post(route('student.ai.generate'), ['teacher_name' => 'Only'])
            ->assertSessionHasErrors(['teacher_id', 'experience', 'language', 'content_type', 'desired_length']);
    }

    private function studentAndTeacher(): array
    {
        $student = User::factory()->create(['role' => Role::Student]);
        $student->studentProfile()->create(['class_name' => 'Class 10 A']);

        $teacherUser = User::factory()->create(['role' => Role::Teacher, 'name' => 'Teacher One']);
        $teacher = Teacher::query()->create([
            'user_id' => $teacherUser->id,
            'slug' => 'teacher-one',
            'designation' => 'Mentor',
            'banner_title' => 'Banner',
            'bio' => 'Bio',
            'quote' => 'Quote',
            'is_active' => true,
        ]);

        return [$student, $teacher];
    }
}

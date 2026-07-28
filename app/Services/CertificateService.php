<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Teacher;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class CertificateService
{
    public function forTeacher(Teacher $teacher): Certificate
    {
        return Certificate::query()->firstOrCreate(
            ['teacher_id' => $teacher->id],
            [
                'certificate_number' => 'GV-'.now()->format('Y').'-'.strtoupper(Str::random(16)),
                'verification_token' => Str::random(48),
                'generated_at' => now(),
            ]
        );
    }

    public function regenerate(Teacher $teacher): Certificate
    {
        $certificate = $this->forTeacher($teacher);
        $certificate->update([
            'certificate_number' => 'GV-'.now()->format('Y').'-'.strtoupper(Str::random(16)),
            'generated_at' => now(),
            'revoked_at' => null,
            'revoked_by' => null,
            'revocation_reason' => null,
        ]);

        return $certificate->refresh();
    }

    public function download(Teacher $teacher, string $verificationUrl, string $qrSvg, array $settings): Response
    {
        $certificate = $this->forTeacher($teacher);

        $pdf = Pdf::loadView('certificates.pdf', [
            'teacher' => $teacher,
            'certificate' => $certificate,
            'verificationUrl' => $verificationUrl,
            'qrSvg' => $qrSvg,
            'settings' => $settings,
        ]);

        return $pdf->download("certificate-{$teacher->slug}.pdf");
    }
}

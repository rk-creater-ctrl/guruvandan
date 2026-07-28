<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Teacher;
use App\Services\ActivityLogService;
use App\Services\CertificateService;
use App\Services\QrCodeService;
use App\Services\SettingsService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CertificateManagementController extends Controller
{
    public function index(): View
    {
        return view('admin.certificates', [
            'teachers' => Teacher::query()->with('user', 'certificate')->withCount('tributes')->orderBy('id')->paginate(15),
        ]);
    }

    public function generate(Request $request, Teacher $teacher, CertificateService $service, ActivityLogService $logs): RedirectResponse
    {
        $certificate = $service->forTeacher($teacher);
        $logs->log($request->user(), 'certificate_generated', $certificate, ['teacher_id' => $teacher->id]);

        return back()->with('status', 'Certificate generated.');
    }

    public function regenerate(Request $request, Teacher $teacher, CertificateService $service, ActivityLogService $logs): RedirectResponse
    {
        $certificate = $service->regenerate($teacher);
        $logs->log($request->user(), 'certificate_regenerated', $certificate, ['teacher_id' => $teacher->id]);

        return back()->with('status', 'Certificate regenerated. Its verification QR remains valid.');
    }

    public function revoke(Request $request, Certificate $certificate, ActivityLogService $logs): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $certificate->update(['revoked_at' => now(), 'revoked_by' => $request->user()->id, 'revocation_reason' => $data['reason']]);
        $logs->log($request->user(), 'certificate_revoked', $certificate, ['reason' => $data['reason']]);

        return back()->with('status', 'Certificate revoked. The verification page now shows its revoked status.');
    }

    public function preview(Teacher $teacher, CertificateService $service, QrCodeService $qr, SettingsService $settings): Response
    {
        return $this->render($teacher, $service, $qr, $settings, false);
    }

    public function download(Teacher $teacher, CertificateService $service, QrCodeService $qr, SettingsService $settings): Response
    {
        return $this->render($teacher, $service, $qr, $settings, true);
    }

    private function render(Teacher $teacher, CertificateService $service, QrCodeService $qr, SettingsService $settings, bool $download): Response
    {
        $certificate = $service->forTeacher($teacher);
        $verificationUrl = route('certificates.verify', $certificate->verification_token);
        $pdf = Pdf::loadView('certificates.pdf', [
            'teacher' => $teacher->load('user'),
            'certificate' => $certificate,
            'verificationUrl' => $verificationUrl,
            'qrSvg' => $qr->svg($verificationUrl),
            'settings' => $settings->all()->toArray(),
        ]);

        return $download
            ? $pdf->download("certificate-{$teacher->slug}.pdf")
            : $pdf->stream("certificate-{$teacher->slug}.pdf");
    }
}

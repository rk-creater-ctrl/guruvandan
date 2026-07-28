<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Teacher;
use App\Services\QrCodeService;
use Illuminate\Http\Response;
use Illuminate\View\View;

class QrController extends Controller
{
    public function teacher(Teacher $teacher, QrCodeService $qr): Response
    {
        abort_unless($teacher->is_active, 404);

        return $this->download($qr->svg(route('teachers.show', $teacher), 700), "teacher-{$teacher->slug}-qr.svg");
    }

    public function certificate(Certificate $certificate, QrCodeService $qr): Response
    {
        return $this->download($qr->svg(route('certificates.verify', $certificate->verification_token), 700), "certificate-{$certificate->certificate_number}-qr.svg");
    }

    public function printTeacher(Teacher $teacher, QrCodeService $qr): View
    {
        abort_unless($teacher->is_active, 404);

        return view('public.qr-print', [
            'title' => $teacher->user->name.' Tribute Page',
            'subtitle' => 'Scan to open the GuruVandan tribute page.',
            'qrSvg' => $qr->svg(route('teachers.show', $teacher), 500),
        ]);
    }

    private function download(string $svg, string $filename): Response
    {
        return response($svg, 200, [
            'Content-Type' => 'image/svg+xml',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}

<?php

namespace App\Http\Requests;

use App\Enums\TributeLanguage;
use App\Enums\TributeType;
use App\Services\SettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStudent() ?? false;
    }

    public function rules(): array
    {
        $imageLimit = (int) app(SettingsService::class)->get('upload_image_kb', 5120);
        $audioLimit = (int) app(SettingsService::class)->get('upload_audio_kb', 12288);
        $videoLimit = (int) app(SettingsService::class)->get('upload_video_kb', 51200);
        $max = max($imageLimit, $audioLimit, $videoLimit);
        $configuredTypes = app(SettingsService::class)->get('upload_allowed_types');
        $allowedTypes = is_string($configuredTypes) ? json_decode($configuredTypes, true) : $configuredTypes;
        $allowedTypes = array_values(array_intersect(
            is_array($allowedTypes) ? $allowedTypes : ['jpg', 'jpeg', 'png', 'webp', 'mp3', 'wav', 'm4a', 'mp4', 'webm'],
            ['jpg', 'jpeg', 'png', 'webp', 'mp3', 'wav', 'm4a', 'mp4', 'webm']
        ));
        if ($allowedTypes === []) {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'webp', 'mp3', 'wav', 'm4a', 'mp4', 'webm'];
        }
        $allowedMimes = array_values(array_unique(array_merge(...array_map(fn (string $extension): array => match ($extension) {
            'jpg', 'jpeg' => ['image/jpeg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
            'mp3' => ['audio/mpeg'],
            'wav' => ['audio/wav', 'audio/x-wav'],
            'm4a' => ['audio/mp4', 'audio/x-m4a'],
            'mp4' => ['video/mp4'],
            'webm' => ['video/webm'],
        }, $allowedTypes))));

        return [
            'teacher_id' => ['required', 'exists:teachers,id'],
            'tribute_type' => ['required', Rule::enum(TributeType::class)],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:10000'],
            'language' => ['required', Rule::enum(TributeLanguage::class)],
            'media' => [
                'nullable',
                'file',
                "max:{$max}",
                'mimes:'.implode(',', $allowedTypes),
                'mimetypes:'.implode(',', $allowedMimes),
            ],
        ];
    }

    public function after(): array
    {
        return [function ($validator): void {
            $file = $this->file('media');
            if (! $file || $validator->errors()->has('media')) {
                return;
            }

            $originalName = (string) $file->getClientOriginalName();
            $baseName = basename(str_replace('\\', '/', $originalName));
            if ((int) $file->getSize() <= 0) {
                $validator->errors()->add('media', 'The selected file is empty. Please choose a valid file.');

                return;
            }

            if ($baseName !== $originalName || str_contains($baseName, '..') || preg_match('/[\x00-\x1F\x7F]/', $baseName)) {
                $validator->errors()->add('media', 'The selected file name is not safe. Please rename the file and upload it again.');

                return;
            }

            $parts = array_filter(explode('.', strtolower($baseName)));
            $blockedInnerExtensions = ['php', 'phtml', 'phar', 'html', 'htm', 'js', 'exe', 'bat', 'cmd', 'com', 'scr', 'svg'];
            if (count($parts) > 2 && array_intersect(array_slice($parts, 0, -1), $blockedInnerExtensions)) {
                $validator->errors()->add('media', 'Files with unsafe double extensions are not accepted.');

                return;
            }

            $limits = [
                'image' => (int) app(SettingsService::class)->get('upload_image_kb', 5120),
                'audio' => (int) app(SettingsService::class)->get('upload_audio_kb', 12288),
                'video' => (int) app(SettingsService::class)->get('upload_video_kb', 51200),
            ];
            $kind = explode('/', (string) $file->getMimeType(), 2)[0];
            if ($kind === 'image' && @getimagesize($file->getRealPath()) === false) {
                $validator->errors()->add('media', 'The selected image could not be read. Please upload a valid JPG, PNG, or WebP file.');

                return;
            }

            if (isset($limits[$kind]) && $file->getSize() > $limits[$kind] * 1024) {
                $validator->errors()->add('media', ucfirst($kind).' exceeds the configured upload limit.');
            }
        }];
    }
}

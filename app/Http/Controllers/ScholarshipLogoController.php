<?php

namespace App\Http\Controllers;

use App\Models\Scholarship;
use finfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ScholarshipLogoController extends Controller
{
    public function show(Request $request, Scholarship $scholarship)
    {
        $user = $request->user();
        $isAdmin = (string) $user->role === '0';
        $isApprovedStudent = (string) $user->role !== '0' && $user->account_status === 'approved';
        abort_unless($isAdmin || $isApprovedStudent, 403);

        $path = $scholarship->logo_path;
        if (!is_string($path)
            || str_contains($path, '..')
            || str_starts_with($path, '/')
            || str_contains($path, '\\')
            || !preg_match('#\Ascholarship-logos/[^/]+\z#D', $path)) {
            abort(404);
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($path)) {
            abort(404);
        }

        $size = $disk->size($path);
        $modifiedAt = $disk->lastModified($path);
        $updatedAt = $scholarship->updated_at?->getTimestamp() ?? 0;
        $etag = '"' . hash('sha256', $scholarship->id . '|' . $path . '|' . $size . '|' . $modifiedAt . '|' . $updatedAt) . '"';
        $headers = [
            'ETag' => $etag,
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Length' => (string) $size,
            'Content-Disposition' => 'inline',
        ];

        if (trim($request->header('If-None-Match', '')) === $etag) {
            return response('', 304, $headers);
        }

        $stream = $disk->readStream($path);
        abort_unless(is_resource($stream), 404);

        $prefix = fread($stream, 8192);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($prefix ?: '');
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            fclose($stream);
            abort(404);
        }

        $headers['Content-Type'] = $mime;

        return response()->stream(function () use ($stream, $prefix): void {
            try {
                echo $prefix;
                fpassthru($stream);
            } finally {
                if (is_resource($stream)) {
                    fclose($stream);
                }
            }
        }, 200, $headers);
    }
}
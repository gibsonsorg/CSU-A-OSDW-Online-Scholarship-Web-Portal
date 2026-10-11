<?php

namespace App\Http\Controllers;

use App\Models\AnnouncementImage;
use finfo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementImageController extends Controller
{
    public function show(Request $request, AnnouncementImage $image)
    {
        $user = $request->user();
        $isAdmin = (string) $user->role === '0';
        $isApprovedStudent = (string) $user->role !== '0' && $user->account_status === 'approved';
        abort_unless($isAdmin || $isApprovedStudent, 403);

        $disk = Storage::disk(config('announcements.disk'));
        if (!$disk->exists($image->path)) {
            abort(404);
        }

        $size = $disk->size($image->path);
        $modifiedAt = $disk->lastModified($image->path);
        $etag = '"' . hash('sha256', $image->id . '|' . $image->path . '|' . $size . '|' . $modifiedAt) . '"';
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

        $stream = $disk->readStream($image->path);
        abort_unless(is_resource($stream), 404);

        $prefix = fread($stream, 8192);
        $mime = (new finfo(FILEINFO_MIME_TYPE))->buffer($prefix ?: '');
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
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
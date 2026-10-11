<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementComment;
use App\Models\AnnouncementImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Illuminate\Support\Str;
use Throwable;

class AdminAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = Announcement::query()
            ->with('images')
            ->withCount('comments')
            ->orderByRaw('pinned_at IS NULL')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function edit(Announcement $announcement)
    {
        $announcement->load('images');

        return view('admin.announcements.edit', compact('announcement'));
    }

    public function comments(Announcement $announcement)
    {
        $comments = $announcement->comments()
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(30);

        return view('admin.announcements.comments', compact('announcement', 'comments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->rejectOversizedRequest($request);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ], $this->validationMessages());
        $images = $this->validatedImages($request, 4);
        $storedPaths = [];

        try {
            DB::transaction(function () use ($request, $validated, $images, &$storedPaths): void {
                $announcement = Announcement::create([
                    'admin_user_id' => $request->user()->id,
                    'body' => $validated['body'],
                ]);

                $this->storeImages($announcement, $images, $storedPaths);
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);
            throw $exception;
        }

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement posted.');
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $this->rejectOversizedRequest($request);
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['required', 'integer'],
        ], $this->validationMessages());

        $existingImages = $announcement->images()->get();
        $removeIds = collect($validated['remove_images'] ?? [])->map(fn ($id) => (int) $id);
        if ($removeIds->diff($existingImages->pluck('id'))->isNotEmpty()) {
            throw ValidationException::withMessages(['remove_images' => 'One or more selected images do not belong to this announcement.']);
        }

        $remainingCount = $existingImages->count() - $removeIds->unique()->count();
        $newImages = $this->validatedImages($request, max(0, 4 - $remainingCount));

        $pathsToDelete = $existingImages->whereIn('id', $removeIds)->pluck('path')->all();
        $storedPaths = [];

        try {
            DB::transaction(function () use ($announcement, $validated, $removeIds, $newImages, &$storedPaths): void {
                $announcement->update(['body' => $validated['body']]);
                if ($removeIds->isNotEmpty()) {
                    $announcement->images()->whereIn('id', $removeIds)->delete();
                }
                $this->storeImages($announcement, $newImages, $storedPaths);
            });
        } catch (Throwable $exception) {
            $this->deleteStoredPaths($storedPaths);
            throw $exception;
        }

        $this->deleteStoredPaths($pathsToDelete);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement updated.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $paths = $announcement->images()->pluck('path')->all();
        DB::transaction(fn () => $announcement->delete());
        $this->deleteStoredPaths($paths);

        return redirect()->route('admin.announcements.index')->with('success', 'Announcement deleted.');
    }

    public function togglePin(Announcement $announcement): RedirectResponse
    {
        $announcement->update(['pinned_at' => $announcement->pinned_at ? null : now()]);

        return redirect()->route('admin.announcements.index')->with('success', $announcement->pinned_at ? 'Announcement pinned.' : 'Announcement unpinned.');
    }

    public function destroyComment(AnnouncementComment $comment): RedirectResponse
    {
        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    private function rejectOversizedRequest(Request $request): void
    {
        $contentLength = (int) ($request->server('CONTENT_LENGTH') ?: $request->header('Content-Length', 0));
        $postMaxSize = $this->parseIniSize((string) ini_get('post_max_size'));

        if ($postMaxSize > 0 && $contentLength > $postMaxSize && $request->allFiles() === []) {
            Log::info('Announcement upload rejected because post_max_size was exceeded.', [
                'content_length' => $contentLength,
                'post_max_size' => ini_get('post_max_size'),
            ]);
            throw ValidationException::withMessages([
                'images' => 'Total upload size is too large, try fewer or smaller images.',
            ]);
        }
    }

    private function parseIniSize(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '0') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function validatedImages(Request $request, int $availableSlots): array
    {
        $files = $request->file('images', []);
        if ($files instanceof UploadedFile) {
            $files = [$files];
        }

        if (!is_array($files)) {
            return [];
        }

        $accepted = [];
        $errors = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $detectedExtensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];

        foreach (array_values($files) as $index => $file) {
            if (!$file instanceof UploadedFile || $file->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $clientName = $file->getClientOriginalName();
            $clientExtension = strtolower($file->getClientOriginalExtension());
            $size = (int) ($file->getSize() ?: 0);
            $mime = $this->detectImageMime($file);
            $reason = $this->imageRejectionReason($file, $clientExtension, $mime, $size, $index, $availableSlots, $allowedExtensions);

            if ($reason !== null) {
                Log::info('Announcement image upload rejected.', [
                    'client_name' => $clientName,
                    'client_extension' => $clientExtension,
                    'detected_type' => $mime,
                    'size' => $size,
                    'reason' => $reason,
                ]);
                $errors['images.' . $index] = '"' . $clientName . '" ' . $reason;
                continue;
            }

            $accepted[] = [
                'file' => $file,
                'extension' => $detectedExtensions[$mime],
            ];
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return $accepted;
    }

    private function detectImageMime(UploadedFile $file): string
    {
        if (!$file->isValid() || !is_file($file->getRealPath())) {
            return 'application/octet-stream';
        }

        return (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()) ?: 'application/octet-stream';
    }

    private function imageRejectionReason(
        UploadedFile $file,
        string $extension,
        string $mime,
        int $size,
        int $index,
        int $availableSlots,
        array $allowedExtensions
    ): ?string {
        if (!$file->isValid()) {
            return match ($file->getError()) {
                UPLOAD_ERR_INI_SIZE => 'exceeds upload_max_filesize (' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_FORM_SIZE => 'exceeds the form upload size limit.',
                UPLOAD_ERR_PARTIAL => 'was only partially uploaded. Please try again.',
                UPLOAD_ERR_NO_TMP_DIR => 'could not be uploaded because the server has no temporary upload directory.',
                UPLOAD_ERR_CANT_WRITE => 'could not be written to temporary storage.',
                UPLOAD_ERR_EXTENSION => 'was rejected by a PHP upload extension.',
                default => 'could not be uploaded. Please try again.',
            };
        }

        if ($size > 2 * 1024 * 1024) {
            return 'is larger than 2MB.';
        }

        if (!isset(['image/jpeg' => true, 'image/png' => true, 'image/webp' => true][$mime])) {
            $type = match ($mime) {
                'image/heic', 'image/heif', 'image/heic-sequence', 'image/heif-sequence' => 'HEIC',
                'image/avif' => 'AVIF',
                'image/gif' => 'GIF',
                'image/svg+xml' => 'SVG',
                'application/pdf' => 'PDF',
                'text/plain' => 'plain text',
                default => $mime,
            };

            if (str_starts_with($mime, 'image/')) {
                return 'is a ' . $type . ' image. Please upload JPG, PNG, or WebP.';
            }

            return 'is not a supported image (detected ' . $type . '). Please upload JPG, PNG, or WebP.';
        }

        if (!in_array($extension, $allowedExtensions, true)) {
            return 'has an unsupported file extension. Please upload JPG, PNG, or WebP.';
        }

        if ($index >= $availableSlots) {
            return 'would exceed the 4-image limit for this post.';
        }

        return null;
    }

    private function validationMessages(): array
    {
        return [
            'body.required' => 'Write announcement text before posting.',
            'body.max' => 'Announcement text cannot exceed 5,000 characters.',
        ];
    }

    private function storeImages(Announcement $announcement, array $images, array &$storedPaths): void
    {
        $sortOrder = (int) $announcement->images()->max('sort_order') + 1;
        foreach ($images as $upload) {
            $file = $upload['file'];
            $filename = Str::random(40) . '.' . $upload['extension'];
            $path = $file->storeAs('announcements', $filename, config('announcements.disk'));
            if (!$path) {
                throw new RuntimeException('The announcement image could not be stored.');
            }

            $storedPaths[] = $path;
            AnnouncementImage::create([
                'announcement_id' => $announcement->id,
                'path' => $path,
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function deleteStoredPaths(array $paths): void
    {
        $disk = Storage::disk(config('announcements.disk'));

        foreach (array_unique($paths) as $path) {
            if (!$disk->exists($path)) {
                Log::warning('Announcement image file is missing during cleanup.', ['path' => $path]);
                continue;
            }

            if (!$disk->delete($path)) {
                Log::warning('Announcement image file could not be deleted.', ['path' => $path]);
            }
        }
    }
}
<?php

namespace Arshad1114\DmsDiskServer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DmsReceiverController extends Controller
{
    // -------------------------------------------------------------------------
    // Resolve disk from request — falls back to app default
    // -------------------------------------------------------------------------

    private function disk(Request $request): string
    {
        return $request->input('disk')
            ?? $request->query('disk')
            ?? config('filesystems.default');
    }

    // -------------------------------------------------------------------------
    // POST /dms-disk/upload
    // -------------------------------------------------------------------------

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'path'       => ['required', 'string', 'max:500'],
            'file'       => ['required', 'file', 'max:' . config('dms-disk-server.max_file_size_kb')],
            'visibility' => ['sometimes', 'string', 'in:public,private'],
            'disk'       => ['sometimes', 'string'],
        ]);

        $disk       = $this->disk($request);
        $file       = $request->file('file');
        $path       = $request->input('path');
        $visibility = $request->input('visibility', 'private');
        $directory  = dirname($path) === '.' ? '' : dirname($path);

        Storage::disk($disk)->putFileAs($directory, $file, basename($path));

        if ($visibility === 'public') {
            Storage::disk($disk)->setVisibility($path, 'public');
        }

        return response()->json([
            'status' => 'ok',
            'path'   => $path,
            'size'   => $file->getSize(),
            'mime'   => $file->getMimeType(),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/file
    // -------------------------------------------------------------------------

    public function download(Request $request): StreamedResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        $disk = $this->disk($request);
        $path = $request->query('path');

        abort_unless(Storage::disk($disk)->exists($path), 404, "File not found: {$path}");

        return Storage::disk($disk)->download($path);
    }

    // -------------------------------------------------------------------------
    // DELETE /dms-disk/file
    // -------------------------------------------------------------------------

    public function delete(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        Storage::disk($this->disk($request))->delete($request->query('path'));

        return response()->json(['status' => 'ok']);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/exists
    // -------------------------------------------------------------------------

    public function exists(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        return response()->json([
            'exists' => Storage::disk($this->disk($request))->exists($request->query('path')),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/url
    // -------------------------------------------------------------------------

    public function url(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        return response()->json([
            'url' => Storage::disk($this->disk($request))->url($request->query('path')),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/temp-url
    // -------------------------------------------------------------------------

    public function temporaryUrl(Request $request): JsonResponse
    {
        $request->validate([
            'path'   => ['required', 'string'],
            'expiry' => ['sometimes', 'integer', 'min:1'],
        ]);

        $disk   = $this->disk($request);
        $expiry = now()->addSeconds((int) $request->query('expiry', 3600));

        return response()->json([
            'url'        => Storage::disk($disk)->temporaryUrl($request->query('path'), $expiry),
            'expires_at' => $expiry->toIso8601String(),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /dms-disk/move
    // -------------------------------------------------------------------------

    public function move(Request $request): JsonResponse
    {
        $request->validate([
            'from' => ['required', 'string'],
            'to'   => ['required', 'string'],
        ]);

        $disk = $this->disk($request);
        $from = $request->input('from');

        abort_unless(Storage::disk($disk)->exists($from), 404, "Source file not found: {$from}");

        Storage::disk($disk)->move($from, $request->input('to'));

        return response()->json(['status' => 'ok']);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/list
    // -------------------------------------------------------------------------

    public function list(Request $request): JsonResponse
    {
        $request->validate([
            'directory' => ['sometimes', 'string'],
            'recursive' => ['sometimes', 'string', 'in:true,false'],
        ]);

        $disk      = $this->disk($request);
        $directory = $request->query('directory', '');
        $recursive = $request->query('recursive', 'false') === 'true';

        $files = $recursive
            ? Storage::disk($disk)->allFiles($directory)
            : Storage::disk($disk)->files($directory);

        return response()->json(['files' => $files]);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/metadata
    // -------------------------------------------------------------------------

    public function metadata(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        $disk = $this->disk($request);
        $path = $request->query('path');

        abort_unless(Storage::disk($disk)->exists($path), 404, "File not found: {$path}");

        return response()->json([
            'path'          => $path,
            'size'          => Storage::disk($disk)->size($path),
            'mime_type'     => Storage::disk($disk)->mimeType($path),
            'visibility'    => Storage::disk($disk)->visibility($path),
            'last_modified' => Storage::disk($disk)->lastModified($path),
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /dms-disk/visibility
    // -------------------------------------------------------------------------

    public function setVisibility(Request $request): JsonResponse
    {
        $request->validate([
            'path'       => ['required', 'string'],
            'visibility' => ['required', 'string', 'in:public,private'],
        ]);

        $disk = $this->disk($request);
        $path = $request->input('path');

        abort_unless(Storage::disk($disk)->exists($path), 404, "File not found: {$path}");

        Storage::disk($disk)->setVisibility($path, $request->input('visibility'));

        return response()->json(['status' => 'ok']);
    }
}

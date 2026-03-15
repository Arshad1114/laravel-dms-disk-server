<?php

namespace Arshad1114\DmsDiskServer\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DmsReceiverController extends Controller
{
    /*
     | No disk resolution logic here — the DMS app's own filesystems.php
     | default disk is always used. The consumer never dictates storage.
     */

    // -------------------------------------------------------------------------
    // POST /dms-disk/upload
    // -------------------------------------------------------------------------

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'path'       => ['required', 'string', 'max:500'],
            'file'       => ['required', 'file', 'max:' . config('dms-disk-server.max_file_size_kb')],
            'visibility' => ['sometimes', 'string', 'in:public,private'],
        ]);

        $file       = $request->file('file');
        $path       = $request->input('path');
        $visibility = $request->input('visibility', 'private');
        $directory  = dirname($path) === '.' ? '' : dirname($path);

        Storage::putFileAs($directory, $file, basename($path));

        if ($visibility === 'public') {
            Storage::setVisibility($path, 'public');
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

        $path = $request->query('path');

        abort_unless(Storage::exists($path), 404, "File not found: {$path}");

        return Storage::download($path);
    }

    // -------------------------------------------------------------------------
    // DELETE /dms-disk/file
    // -------------------------------------------------------------------------

    public function delete(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        Storage::delete($request->query('path'));

        return response()->json(['status' => 'ok']);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/exists
    // -------------------------------------------------------------------------

    public function exists(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        return response()->json([
            'exists' => Storage::exists($request->query('path')),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/url
    // -------------------------------------------------------------------------

    public function url(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        return response()->json([
            'url' => Storage::url($request->query('path')),
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

        $expiry = now()->addSeconds((int) $request->query('expiry', 3600));

        return response()->json([
            'url'        => Storage::temporaryUrl($request->query('path'), $expiry),
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

        $from = $request->input('from');

        abort_unless(Storage::exists($from), 404, "Source file not found: {$from}");

        Storage::move($from, $request->input('to'));

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

        $directory = $request->query('directory', '');
        $recursive = $request->query('recursive', 'false') === 'true';

        $files = $recursive
            ? Storage::allFiles($directory)
            : Storage::files($directory);

        return response()->json(['files' => $files]);
    }

    // -------------------------------------------------------------------------
    // GET /dms-disk/metadata
    // -------------------------------------------------------------------------

    public function metadata(Request $request): JsonResponse
    {
        $request->validate(['path' => ['required', 'string']]);

        $path = $request->query('path');

        abort_unless(Storage::exists($path), 404, "File not found: {$path}");

        return response()->json([
            'path'          => $path,
            'size'          => Storage::size($path),
            'mime_type'     => Storage::mimeType($path),
            'visibility'    => Storage::visibility($path),
            'last_modified' => Storage::lastModified($path),
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

        $path = $request->input('path');

        abort_unless(Storage::exists($path), 404, "File not found: {$path}");

        Storage::setVisibility($path, $request->input('visibility'));

        return response()->json(['status' => 'ok']);
    }
}

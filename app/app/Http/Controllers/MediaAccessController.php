<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaAccessController extends Controller
{
    use AuthorizesRequests;

    public function signed(Request $request, Media $media): StreamedResponse
    {
        abort_unless($request->user() !== null, 401);

        $this->authorize('view', $media);

        return $media->toResponse($request);
    }

    public function update(Request $request, Media $media): Response
    {
        $this->authorize('update', $media);

        $media->update($request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]));

        return response()->noContent();
    }

    public function destroy(Media $media): Response
    {
        $this->authorize('delete', $media);

        $media->delete();

        return response()->noContent();
    }
}

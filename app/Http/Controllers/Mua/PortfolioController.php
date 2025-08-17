<?php

namespace App\Http\Controllers\Mua;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Portfolio;
use App\Services\ImageUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PortfolioController extends Controller
{
    protected $imageUploadService;

    public function __construct(ImageUploadService $imageUploadService)
    {
        $this->imageUploadService = $imageUploadService;
    }

    public function index()
    {
        $items = Auth::user()->portfolios;
        return response()->json($items);
    }

    public function store(Request $request)
    {
        $request->validate([
            'media_type' => 'required|in:image,video',
            'file'       => 'required|file|max:10240',
            'caption'    => 'nullable|string'
        ]);

        if ($request->hasFile('file'))
            $filename = $this->imageUploadService->uploadPortfolioImage($request->file('file'));

        $item = Portfolio::create([
            'mua_id'     => Auth::id(),
            'media_type' => $request->media_type,
            'media_url'  => $filename,
            'caption'    => $request->caption
        ]);

        return response()->json([
            'message' => 'Media uploaded',
            'data'    => $item
        ]);
    }

    public function destroy($id)
    {
        $item = Portfolio::where('id', $id)
            ->where('mua_id', Auth::id())
            ->firstOrFail();

        if ($item->media_url)
            $this->imageUploadService->deleteImage($item->media_url, 'images/portfolio');

        $item->delete();

        return response()->json(['message' => 'Media deleted']);
    }
}

<?php

namespace App\Modules\Help\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Help\Models\HelpArticle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HelpCenterController extends Controller
{
    public function index(): View
    {
        $articles = HelpArticle::query()
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->paginate(9);

        return view('help::index', ['articles' => $articles]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'in:tutorial,consejo'],
            'excerpt' => ['required', 'string', 'max:500'],
            'body' => ['nullable', 'string', 'max:10000'],
            'video' => ['nullable', 'file', 'mimes:mp4,webm', 'max:204800'],
        ]);

        $videoPath = $request->hasFile('video')
            ? $request->file('video')->store(HelpArticle::VIDEO_DIRECTORY, HelpArticle::DISK)
            : null;

        HelpArticle::query()->create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']).'-'.Str::lower(Str::random(5)),
            'category' => $validated['category'],
            'excerpt' => $validated['excerpt'],
            'body' => $validated['body'] ?? null,
            'video_path' => $videoPath ?: null,
            'is_published' => true,
            'published_at' => now(),
        ]);

        return redirect()->route('help.index')->with('status', 'Artículo publicado en el Centro de Ayuda.');
    }

    public function destroy(HelpArticle $article): RedirectResponse
    {
        if ($article->video_path) {
            Storage::disk(HelpArticle::DISK)->delete($article->video_path);
        }

        $article->delete();

        return redirect()->route('help.index')->with('status', 'Artículo eliminado.');
    }
}

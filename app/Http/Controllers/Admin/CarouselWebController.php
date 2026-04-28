<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomeCarouselSlide;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class CarouselWebController extends Controller
{
    public function index(): Response
    {
        $slides = HomeCarouselSlide::query()->ordered()->get();

        return AdminInertia::frame('admin.carousel.index', compact('slides'));
    }

    public function create(): Response
    {
        return AdminInertia::frame('admin.carousel.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $hasFile = $request->hasFile('image_upload');
        $imageUrl = trim((string) ($data['image'] ?? ''));
        if (! $hasFile && $imageUrl === '') {
            return back()->withErrors(['image' => __('admin.carousel.image_required')])->withInput();
        }

        unset($data['image_upload']);
        $data['is_active'] = $request->boolean('is_active');

        $sort = $data['sort_order'] ?? null;
        if ($sort === null || $sort === '' || (int) $sort < 0) {
            $data['sort_order'] = (int) (HomeCarouselSlide::query()->max('sort_order') ?? 0) + 1;
        } else {
            $data['sort_order'] = (int) $sort;
        }

        $data['image'] = $hasFile ? null : $imageUrl;

        $slide = HomeCarouselSlide::query()->create($data);

        if ($hasFile) {
            $this->storeUploadedImage($slide, $request);
        }

        return redirect()->route('admin.carousel.index')->with('status', __('admin.flash.carousel_slide_created'));
    }

    public function edit(HomeCarouselSlide $slide): Response
    {
        return AdminInertia::frame('admin.carousel.edit', compact('slide'));
    }

    public function update(Request $request, HomeCarouselSlide $slide): RedirectResponse
    {
        $data = $this->validated($request);
        $hasFile = $request->hasFile('image_upload');
        unset($data['image_upload']);
        $data['is_active'] = $request->boolean('is_active');

        if (! array_key_exists('sort_order', $data) || $data['sort_order'] === null || $data['sort_order'] === '') {
            unset($data['sort_order']);
        } else {
            $data['sort_order'] = (int) $data['sort_order'];
        }

        $imageText = trim((string) ($data['image'] ?? ''));
        if (! $hasFile && $imageText === '') {
            return back()->withErrors(['image' => __('admin.carousel.image_required')])->withInput();
        }

        if ($hasFile) {
            $slide->deleteStoredImageFiles();
            unset($data['image']);
        } else {
            $newUrl = $imageText;
            $oldUrl = trim((string) ($slide->image ?? ''));
            if ($slide->usesManagedStorage() && $newUrl !== $oldUrl) {
                $slide->deleteStoredImageFiles();
            }
        }

        $slide->fill($data);
        if ($hasFile) {
            $slide->image = null;
        }
        $slide->save();

        if ($hasFile) {
            $this->storeUploadedImage($slide, $request);
        }

        return redirect()->route('admin.carousel.index')->with('status', __('admin.flash.carousel_slide_updated'));
    }

    public function destroy(HomeCarouselSlide $slide): RedirectResponse
    {
        $slide->delete();

        return redirect()->route('admin.carousel.index')->with('status', __('admin.flash.carousel_slide_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:99999'],
            'title' => ['required', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:2000'],
            'subtitle_en' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'string', 'max:2048'],
            'image_upload' => ['nullable', 'file', 'max:5120', 'mimes:jpg,jpeg,png,webp,gif'],
        ]);
    }

    private function storeUploadedImage(HomeCarouselSlide $slide, Request $request): void
    {
        $file = $request->file('image_upload');
        if (! $file) {
            return;
        }

        $ext = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg'));
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $ext = 'jpg';
        }

        $filename = $slide->id.'.'.$ext;
        $path = $file->storeAs('site/carousel', $filename, 'public');
        $slide->forceFill(['image' => 'storage/'.$path])->save();
    }
}

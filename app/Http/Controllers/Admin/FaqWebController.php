<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class FaqWebController extends Controller
{
    public function index(): Response
    {
        $faqs = Faq::query()->ordered()->get();

        return AdminInertia::frame('admin.faq.index', compact('faqs'));
    }

    public function create(): Response
    {
        return AdminInertia::frame('admin.faq.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->normalizeSortOrderInput($request);
        $data = $this->validated($request);
        $data['sort_order'] = $this->resolveSortOrder($data['sort_order'] ?? null);

        Faq::query()->create($data);

        return redirect()->route('admin.faqs.index')->with('status', __('admin.flash.faq_created'));
    }

    public function edit(Faq $faq): Response
    {
        return AdminInertia::frame('admin.faq.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $this->normalizeSortOrderInput($request);
        $data = $this->validated($request);
        if (array_key_exists('sort_order', $data) && $data['sort_order'] !== null && $data['sort_order'] !== '') {
            $data['sort_order'] = (int) $data['sort_order'];
        } else {
            unset($data['sort_order']);
        }

        $faq->update($data);

        return redirect()->route('admin.faqs.index')->with('status', __('admin.flash.faq_updated'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        return redirect()->route('admin.faqs.index')->with('status', __('admin.flash.faq_deleted'));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'question' => ['required', 'string', 'max:500'],
            'question_en' => ['nullable', 'string', 'max:500'],
            'answer' => ['required', 'string', 'max:20000'],
            'answer_en' => ['nullable', 'string', 'max:20000'],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:99999'],
        ]);
    }

    private function normalizeSortOrderInput(Request $request): void
    {
        if ($request->input('sort_order') === '') {
            $request->merge(['sort_order' => null]);
        }
    }

    private function resolveSortOrder(null|int|string $sort): int
    {
        if ($sort === null || $sort === '') {
            return (int) (Faq::query()->max('sort_order') ?? 0) + 1;
        }

        return max(0, (int) $sort);
    }
}

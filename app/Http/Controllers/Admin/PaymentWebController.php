<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentRequest;
use App\Support\AdminInertia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentWebController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'status' => $request->query('status'),
            'course_id' => $request->query('course_id'),
            'user_id' => $request->query('user_id'),
        ];

        $q = PaymentRequest::query()->with([
            'user:id,name,email',
            'course:id,title',
        ])->latest('id');

        if (is_string($filters['status']) && in_array($filters['status'], ['pending', 'approved', 'rejected'], true)) {
            $q->where('status', $filters['status']);
        }
        if (is_numeric($filters['course_id'])) {
            $q->where('course_id', (int) $filters['course_id']);
        }
        if (is_numeric($filters['user_id'])) {
            $q->where('user_id', (int) $filters['user_id']);
        }

        $payments = $q->paginate(40)->withQueryString();

        return AdminInertia::frame('admin.payments.index', compact('payments', 'filters'));
    }

    public function show(PaymentRequest $paymentRequest): Response
    {
        $paymentRequest->load([
            'user:id,name,email,university,study_year,study_term,role',
            'course:id,title,teacher_id',
        ]);

        return AdminInertia::frame('admin.payments.show', ['payment' => $paymentRequest]);
    }

    public function review(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'note' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($paymentRequest->status !== 'pending') {
            return back()->withErrors(['status' => __('admin.flash.payment_already_reviewed')]);
        }

        $paymentRequest->forceFill([
            'status' => $data['status'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
            'review_note' => $data['note'] ?? null,
        ])->save();

        return redirect()->route('admin.payments.show', $paymentRequest)->with('status', __('admin.flash.payment_review_saved'));
    }

    public function receipt(PaymentRequest $paymentRequest): StreamedResponse|RedirectResponse
    {
        if ($paymentRequest->receipt_path === null || $paymentRequest->receipt_storage_disk === null) {
            return redirect()->back()->withErrors(['receipt' => 'لا يوجد إيصال مرفوع.']);
        }

        $disk = Storage::disk($paymentRequest->receipt_storage_disk);

        if (! $disk->exists($paymentRequest->receipt_path)) {
            return redirect()->back()->withErrors(['receipt' => 'الملف غير موجود على الخادم.']);
        }

        return $disk->response($paymentRequest->receipt_path);
    }
}

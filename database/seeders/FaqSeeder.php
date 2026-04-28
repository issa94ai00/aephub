<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FaqSeeder extends Seeder
{
    /**
     * أسئلة شائعة بالعربية والإنجليزية لمنصة تعليم إلكتروني.
     */
    public function run(): void
    {
        if (! Schema::hasTable('faqs')) {
            return;
        }

        Faq::query()->delete();

        $rows = [
            [
                'question' => 'كيف أتعلّم على المنصة؟',
                'question_en' => 'How do I learn on the platform?',
                'answer' => 'أنشئ حساباً، ثم تصفّح الدورات المتاحة واختر ما يناسبك. بعد التسجيل في الدورة يمكنك متابعة الدروس بالترتيب من صفحة الدورة.',
                'answer_en' => 'Create an account, browse available courses, and pick what fits you. After enrolling, follow lessons in order from the course page.',
                'sort_order' => 10,
            ],
            [
                'question' => 'هل المحتوى يعمل على الجوال والتابلت؟',
                'question_en' => 'Does the content work on phones and tablets?',
                'answer' => 'نعم، واجهة المنصة متجاوبة وتعمل على مختلف أحجام الشاشات لراحة المتابعة من أي جهاز.',
                'answer_en' => 'Yes. The interface is responsive and works across screen sizes so you can learn from any device.',
                'sort_order' => 20,
            ],
            [
                'question' => 'كيف أسجّل في دورة؟',
                'question_en' => 'How do I enroll in a course?',
                'answer' => 'من صفحة الدورة اضغط على التسجيل أو الاشتراك واتبع الخطوات. قد يُطلب تأكيد الدفع أو قبول الشروط حسب إعدادات الدورة.',
                'answer_en' => 'From the course page, tap enroll or subscribe and follow the steps. Payment confirmation or accepting terms may be required depending on the course.',
                'sort_order' => 30,
            ],
            [
                'question' => 'هل تظهر الدورات الجديدة فور نشرها؟',
                'question_en' => 'Do new courses appear as soon as they are published?',
                'answer' => 'نعم، الدورات ذات الحالة «منشورة» تظهر في قائمة الدورات والصفحة الرئيسية وفق ترتيب النظام.',
                'answer_en' => 'Yes. Courses marked as published appear in the course list and home page according to the system ordering.',
                'sort_order' => 40,
            ],
            [
                'question' => 'ماذا أفعل إن واجهت مشكلة تقنية أو في الدفع؟',
                'question_en' => 'What if I have a technical or payment issue?',
                'answer' => 'راسلنا عبر البريد أو الهاتف أو واتساب المذكور في أسفل الموقع، مع ذكر اسم الدورة ورقم حسابك إن أمكن لتسريع المساعدة.',
                'answer_en' => 'Contact us by email, phone, or WhatsApp listed in the site footer. Include the course name and your account details if possible to speed up support.',
                'sort_order' => 50,
            ],
            [
                'question' => 'هل يُمنح إثبات إتمام أو شهادة؟',
                'question_en' => 'Is there a certificate or proof of completion?',
                'answer' => 'يعتمد على سياسة كل دورة ومدرّسها. إن وُجدت شهادة أو شارة إتمام، ستُوضَح في وصف الدورة أو داخل المحتوى بعد إكمال المتطلبات.',
                'answer_en' => 'It depends on each course and instructor. If a certificate or badge exists, it will be explained in the course description or inside the content after you meet the requirements.',
                'sort_order' => 60,
            ],
            [
                'question' => 'هل يمكن مشاركة حسابي مع شخص آخر؟',
                'question_en' => 'Can I share my account with someone else?',
                'answer' => 'يُفضَّل عدم مشاركة بيانات الدخول؛ حسابك مخصص لك ولضمان أمان التقدّم والشهادات إن وُجدت.',
                'answer_en' => 'Avoid sharing login details. Your account is personal and helps protect your progress and any certificates.',
                'sort_order' => 70,
            ],
            [
                'question' => 'كيف أحمي خصوصيتي وبياناتي؟',
                'question_en' => 'How do I protect my privacy and data?',
                'answer' => 'استخدم كلمة مرور قوية، لا تشارك رمز الدخول، واطّلع على سياسة الخصوصية إن وُجدت على المنصة. للاستفسارات تواصل مع فريق الدعم.',
                'answer_en' => 'Use a strong password, do not share OTP codes, and read the privacy policy if available. For questions, contact support.',
                'sort_order' => 80,
            ],
        ];

        foreach ($rows as $row) {
            Faq::query()->create($row);
        }
    }
}

<?php

namespace Database\Seeders;

/**
 * Creates the KPI online landing page on the "landing-kpi" template, filled
 * with the content of the approved design (kpi-landing/index.html, v2.10,
 * 2026-10-31).
 *
 * php artisan db:seed --class=LandingKpiPageSeeder
 */
class LandingKpiPageSeeder extends LandingCoursePageSeeder
{
    protected const PAGE_SLUG = 'kpis-online';

    protected const COURSE_SLUG = 'kpis-online';

    protected const IMAGE_DIRECTORY = 'landing-kpi';

    protected function pageAttributes(): array
    {
        return [
            'title' => ['ar' => 'دورة مؤشرات الأداء KPI عن بعد', 'en' => 'KPI Online Course'],
            'template' => 'landing-kpi',
            'meta_title' => ['ar' => 'دورة مؤشرات الأداء KPI عن بعد | مركز مران للتدريب', 'en' => 'KPI Online Course | Miran Training Center'],
            'meta_description' => [
                'ar' => 'دورة مؤشرات قياس الأداء KPI عن بعد من مركز مران للتدريب: 25 ساعة (20 مباشرة و5 موجّهة)، خمس أمسيات، بـ899 ريال. تبني مؤشرات قسمك جاهزة تعرضها على مديرك. سجّل جوالك وسيتصل بك مستشار التدريب.',
                'en' => 'Online KPI course by Miran Training Center: 25 hours (20 live, 5 guided) over five evenings for SAR 899. Build your department’s KPIs, ready to present to your manager. Leave your number and a training advisor will call you.',
            ],
        ];
    }

    protected function content(): array
    {
        return [
            'heading' => [
                'ar' => 'مديرك يسألك: *كيف أداء القسم؟* جاوبه بلوحة تحكم.',
                'en' => 'Your manager asks: *how is the department doing?* Answer with a dashboard.',
            ],
            'subheading' => [
                'ar' => 'خمس أمسيات مباشرة عن بعد من بيتك، تبني فيها مؤشرات الأداء لقسمك أنت، جاهزة تعرضها على مديرك.',
                'en' => 'Five live evenings online from home where you build the KPIs of your own department, ready to present to your manager.',
            ],
            'price' => '899',
            'old_price' => '999',
            'installment' => [
                'ar' => 'أو 3 دفعات × *299.67 ريال* شهريًا بلا رسوم تأخير مع',
                'en' => 'Or 3 payments × *SAR 299.67* a month, no late fees, with',
            ],
            'facts' => [
                ['value_ar' => '25 ساعة', 'value_en' => '25 hours', 'label_ar' => 'تدريب مباشر ومسجل', 'label_en' => 'Live & recorded training'],
                ['value_ar' => '5', 'value_en' => '5', 'label_ar' => 'أمسيات عن بعد', 'label_en' => 'Online evenings'],
                ['value_ar' => '20', 'value_en' => '20', 'label_ar' => 'تطبيقًا على قسمك', 'label_en' => 'Exercises on your department'],
            ],
            'tools_title' => ['ar' => 'خمس أدوات *تأخذها لقسمك*', 'en' => 'Five tools *you take to your department*'],
            'tools' => [
                ['ar' => 'ورقة تشرح أهم رقم في قسمك', 'en' => 'A sheet explaining your department’s key number'],
                ['ar' => 'خريطة تربط قسمك بأهداف الإدارة', 'en' => 'A map linking your department to management goals'],
                ['ar' => 'صفحة واحدة تلخّص أداء قسمك', 'en' => 'One page summing up your department’s performance'],
                ['ar' => 'خطة جاهزة تبدأ بها', 'en' => 'A ready plan to start with'],
                ['ar' => 'طريقة تكشف سبب تراجع أي رقم', 'en' => 'A method to find why any number drops'],
            ],
            'extras' => [
                ['ar' => 'منصة تعلّم خاصة', 'en' => 'Own learning platform'],
                ['ar' => 'كراسة 20 تطبيقًا', 'en' => 'Workbook of 20 exercises'],
                ['ar' => 'تسجيلات سنتين', 'en' => 'Two years of recordings'],
                ['ar' => 'ورشة تقوية مجانية', 'en' => 'Free refresher workshop'],
                ['ar' => 'شهادة معتمدة', 'en' => 'Accredited certificate'],
            ],
            'stories' => [
                [
                    'title_ar' => 'تميّز الرقم الذي يصنع قرارًا',
                    'title_en' => 'Spot the number that drives a decision',
                    'summary_ar' => 'المؤشر رقم يجيب عن سؤال «هل نسير كما يجب؟»، وليس أي رقم يُكتب في التقرير.',
                    'summary_en' => 'A KPI is a number that answers “are we on track?”, not just any number written in a report.',
                    'now_ar' => 'تقريرك فيه «عدد الطلبات المنجزة هذا الشهر: 340» ولا أحد يعرف هل هذا جيد أم لا.',
                    'now_en' => 'Your report says “requests completed this month: 340” and nobody knows whether that is good.',
                    'after_ar' => '«نسبة الطلبات المنجزة في وقتها المحدد: 78%، والمستهدف 90%»، فيعرف الجميع ما يجب تحسينه.',
                    'after_en' => '“Requests completed on time: 78%, target 90%”, so everyone knows what to improve.',
                ],
                [
                    'title_ar' => 'تبني المؤشر من الهدف إلى الرقم',
                    'title_en' => 'Build the KPI from goal to number',
                    'summary_ar' => 'تبدأ من هدف الإدارة وتنتهي برقم له مصدر ومستهدف ومسؤول، في سبع خطوات واضحة.',
                    'summary_en' => 'Start from a management goal and end with a number that has a source, a target and an owner, in seven clear steps.',
                    'now_ar' => 'الإدارة تقول «نريد خدمة أسرع» وتبقى جملة لا يقيسها أحد.',
                    'now_en' => 'Management says “we want faster service” and it stays a sentence nobody measures.',
                    'after_ar' => '«متوسط إنجاز الطلب: 3 أيام، المصدر نظام الطلبات، المسؤول مشرف القسم، المستهدف يومان».',
                    'after_en' => '“Average request time: 3 days, source: the requests system, owner: the section supervisor, target: 2 days”.',
                ],
                [
                    'title_ar' => 'تقيس القسم من كل جوانبه',
                    'title_en' => 'Measure the department from every side',
                    'summary_ar' => 'القسم ليس إنتاجًا فقط؛ له جودة وسرعة وتكلفة وموظفون وأثر على المستفيدين.',
                    'summary_en' => 'A department is not just output; it has quality, speed, cost, people and impact on its customers.',
                    'now_ar' => 'مؤشر واحد «عدد المعاملات المنجزة» يرتفع، والأخطاء ترتفع معه دون أن يلاحظ أحد.',
                    'now_en' => 'One KPI, “transactions completed”, goes up and errors go up with it without anyone noticing.',
                    'after_ar' => 'بجانبه «نسبة الأخطاء» و«رضا الموظفين»، فترى الصورة كاملة لا جزءًا منها.',
                    'after_en' => 'Next to it sit “error rate” and “staff satisfaction”, so you see the whole picture, not part of it.',
                ],
                [
                    'title_ar' => 'تقرأ التراجع قبل أن يصبح مشكلة',
                    'title_en' => 'Read a decline before it becomes a problem',
                    'summary_ar' => 'الرقم الأحمر بداية البحث عن السبب، لا نهاية القصة.',
                    'summary_en' => 'A red number is where the search for the cause starts, not the end of the story.',
                    'now_ar' => 'رضا العملاء ينخفض شهرين متتاليين ولا يُلاحَظ إلا في اجتماع نهاية السنة.',
                    'now_en' => 'Customer satisfaction drops two months in a row and is only noticed in the year-end meeting.',
                    'after_ar' => 'تكتشف الانخفاض في شهره الأول، وتصل إلى سببه (نقص موظفي المناوبة)، وتوثّق الإجراء في بطاقة المؤشر.',
                    'after_en' => 'You catch the drop in its first month, find the cause (too few shift staff) and record the action on the KPI card.',
                ],
                [
                    'title_ar' => 'تعرض أداء القسم في صفحة واحدة',
                    'title_en' => 'Show the department’s performance on one page',
                    'summary_ar' => 'لوحة التحكم صفحة فيها أهم خمسة أرقام بالأخضر والأحمر، تُفهم في دقيقة.',
                    'summary_en' => 'A dashboard is one page with the five key numbers in green and red, understood in a minute.',
                    'now_ar' => 'مديرك يسأل «كيف أداء القسم؟» فتجمع له عشرة تقارير وتحتاج يومًا لتجيب.',
                    'now_en' => 'Your manager asks “how is the department doing?” and you gather ten reports and need a day to answer.',
                    'after_ar' => 'تفتح صفحة واحدة، والجواب أمامه بالأرقام والألوان في اللحظة نفسها.',
                    'after_en' => 'You open one page and the answer is in front of them, in numbers and colours, right away.',
                ],
                [
                    'title_ar' => 'تكسب دعم الإدارة وتعاون الزملاء',
                    'title_en' => 'Win management support and team cooperation',
                    'summary_ar' => 'المؤشرات تفشل حين يظنها الموظفون أداة عقاب، وتنجح حين تصبح شأنًا مشتركًا.',
                    'summary_en' => 'KPIs fail when staff see them as a punishment tool and succeed when they become a shared concern.',
                    'now_ar' => 'الفريق يتحفظ على الأرقام ويقدّمها متأخرة أو ناقصة.',
                    'now_en' => 'The team is wary of the numbers and hands them in late or incomplete.',
                    'after_ar' => 'تعرض الخطة على مديرك في اجتماع واحد، وتشرح للفريق أن الأرقام للتحسين لا للمحاسبة، فيتعاونون.',
                    'after_en' => 'You present the plan to your manager in one meeting and show the team the numbers are for improving, not blaming, so they cooperate.',
                ],
            ],
        ];
    }
}

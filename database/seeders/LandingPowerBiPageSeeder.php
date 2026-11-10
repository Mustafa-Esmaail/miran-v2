<?php

namespace Database\Seeders;

/**
 * Creates the Power BI online landing page on the "landing-powerbi" template,
 * filled with the content of the approved design (powerbi-landing/index.html,
 * v1.2, 2026-11-01).
 *
 * php artisan db:seed --class=LandingPowerBiPageSeeder
 */
class LandingPowerBiPageSeeder extends LandingCoursePageSeeder
{
    protected const PAGE_SLUG = 'Power-BI';

    protected const COURSE_SLUG = 'power-bi';

    protected const IMAGE_DIRECTORY = 'landing-powerbi';

    protected function pageAttributes(): array
    {
        return [
            'title' => ['ar' => 'تحليل البيانات بـ Power BI عن بعد', 'en' => 'Data Analysis with Power BI Online'],
            'template' => 'landing-powerbi',
            'meta_title' => ['ar' => 'تحليل البيانات بـ Power BI عن بعد | مركز مران للتدريب', 'en' => 'Data Analysis with Power BI Online | Miran Training Center'],
            'meta_description' => [
                'ar' => 'دورة تحليل البيانات بـ Power BI عن بعد من مركز مران للتدريب: 30 ساعة تدريبية، منها 20 مباشرة في خمس أمسيات، بـ1,199 ريال. تجمع ملفاتك في تقرير واحد، وتنظّفها مرة واحدة، وتعرضها في لوحة معلومات تفاعلية. سجّل جوالك وسيتصل بك مستشار التدريب.',
                'en' => 'Online Power BI data analysis course by Miran Training Center: 30 training hours, 20 of them live over five evenings, for SAR 1,199. Combine your files into one report, clean them once and present them in an interactive dashboard. Leave your number and a training advisor will call you.',
            ],
        ];
    }

    protected function content(): array
    {
        return [
            'heading' => [
                'ar' => 'تجمع تقريرك من ثلاثة ملفات *كل شهر؟*',
                'en' => 'Building your report from three files *every month?*',
            ],
            'subheading' => [
                'ar' => 'خمس أمسيات مباشرة عن بعد من بيتك، تربط فيها ملفاتك كلها في لوحة معلومات تفاعلية واحدة، تتحدّث بضغطة وتعرضها لمديرك كما هي.',
                'en' => 'Five live evenings online from home where you connect all your files into one interactive dashboard that refreshes with a click and goes to your manager as is.',
            ],
            'price' => '1,199',
            'old_price' => '1,699',
            'installment' => [
                'ar' => 'أو 3 دفعات × *399.67 ريال* شهريًا بلا رسوم تأخير مع',
                'en' => 'Or 3 payments × *SAR 399.67* a month, no late fees, with',
            ],
            'facts' => [
                ['value_ar' => '30 ساعة', 'value_en' => '30 hours', 'label_ar' => 'تدريب مباشر ومسجل', 'label_en' => 'Live & recorded training'],
                ['value_ar' => '5', 'value_en' => '5', 'label_ar' => 'أمسيات مسائية', 'label_en' => 'Evenings'],
                ['value_ar' => '27', 'value_en' => '27', 'label_ar' => 'تطبيقًا عمليًا', 'label_en' => 'Hands-on exercises'],
            ],
            'tools_title' => ['ar' => 'خمس أدوات *تأخذها لعملك*', 'en' => 'Five tools *you take to work*'],
            'tools' => [
                ['ar' => 'تقرير واحد يجمع ملفاتك كلها ويتحدّث بضغطة', 'en' => 'One report that combines all your files and refreshes with a click'],
                ['ar' => 'جداول مرتبطة ببعضها بلا دوال بحث', 'en' => 'Linked tables without lookup functions'],
                ['ar' => 'رسوم تقارن الأقسام والأشهر بضغطة', 'en' => 'Charts comparing departments and months in a click'],
                ['ar' => 'أي رقم تضغط عليه يفتح لك تفاصيله', 'en' => 'Any number you click opens its details'],
                ['ar' => 'لوحة معلومات تفاعلية في صفحة واحدة تعرضها كما هي', 'en' => 'A one-page interactive dashboard you present as is'],
            ],
            'extras' => [
                ['ar' => 'منصة تعلّم خاصة', 'en' => 'Own learning platform'],
                ['ar' => 'كراسة 27 تطبيقًا', 'en' => 'Workbook of 27 exercises'],
                ['ar' => 'تسجيلات سنتين', 'en' => 'Two years of recordings'],
                ['ar' => 'ورشة تقوية مجانية', 'en' => 'Free refresher workshop'],
                ['ar' => 'شهادة معتمدة', 'en' => 'Accredited certificate'],
            ],
            'stories' => [
                [
                    'title_ar' => 'تجمع ملفاتك في تقرير واحد',
                    'title_en' => 'Combine your files into one report',
                    'summary_ar' => 'محرر الاستعلامات (Power Query) يربط ملفات الإكسل وقواعد البيانات بالتقرير مباشرة، ويقرأ الجديد منها بضغطة تحديث.',
                    'summary_en' => 'Power Query connects Excel files and databases straight to the report and reads new data with one refresh.',
                    'now_ar' => 'كل شهر تنسخ من ملف المبيعات وملف العملاء وملف المستهدفات في ملف واحد، وتعيد الجمع من أول وجديد.',
                    'now_en' => 'Every month you copy the sales, customers and targets files into one file and rebuild everything from scratch.',
                    'after_ar' => 'تربط الملفات الثلاثة مرة واحدة، وفي الشهر التالي ضغطة واحدة تقرأ الأرقام الجديدة بدل إعادة الجمع.',
                    'after_en' => 'You connect the three files once; next month one click reads the new numbers instead of rebuilding.',
                ],
                [
                    'title_ar' => 'تنظّف البيانات مرة واحدة فقط',
                    'title_en' => 'Clean the data only once',
                    'summary_ar' => 'خطوات التنظيف (حذف المكرر، تعبئة الفراغات، فصل المدينة عن الحي) تُحفظ في سجل، وتُعاد تلقائيًا على كل ملف جديد.',
                    'summary_en' => 'Cleaning steps (removing duplicates, filling blanks, splitting city from district) are saved and replayed on every new file.',
                    'now_ar' => 'قبل كل تقرير تحذف المكرر وتصحّح الفراغات وتفصل الأعمدة الملتصقة يدويًا.',
                    'now_en' => 'Before every report you remove duplicates, fix blanks and split joined columns by hand.',
                    'after_ar' => 'تسجّل الخطوات مرة واحدة في المحرر، وتُطبَّق وحدها على بيانات الشهر القادم كاملة.',
                    'after_en' => 'You record the steps once in the editor and they apply by themselves to all of next month’s data.',
                ],
                [
                    'title_ar' => 'تربط الجداول بسحبة بدل دالة البحث',
                    'title_en' => 'Link tables with a drag instead of a lookup',
                    'summary_ar' => 'العلاقات (Relationships) تجعل جدول العملاء وجدول المبيعات يعملان كجدول واحد دون نسخ أعمدة بينهما.',
                    'summary_en' => 'Relationships make the customers and sales tables work as one, without copying columns between them.',
                    'now_ar' => 'تسحب اسم العميل ومدينته إلى جدول المبيعات بدالة بحث، وتصلح الأخطاء كلما تغيّر ملف.',
                    'now_en' => 'You pull the customer’s name and city into the sales table with a lookup and fix errors whenever a file changes.',
                    'after_ar' => 'تسحب خطًا بين الجدولين في شاشة النموذج، فيفلتر مخطط المبيعات حسب فئة العميل مباشرة.',
                    'after_en' => 'You drag a line between the two tables in the model view and the sales chart filters by customer type right away.',
                ],
                [
                    'title_ar' => 'أي رقم تضغط عليه يفتح لك تفاصيله',
                    'title_en' => 'Any number you click opens its details',
                    'summary_ar' => 'التعمّق (Drill Down) يفتح الرقم الكبير إلى ما تحته: القسم ثم البند ثم الشهر.',
                    'summary_en' => 'Drill down opens a big number into what is beneath it: department, then item, then month.',
                    'now_ar' => 'مديرك يسأل: «ليش ارتفعت مصروفات هذا الشهر؟» فتبني تقريرًا جديدًا لتعرف أي قسم هو السبب.',
                    'now_en' => 'Your manager asks “why did expenses rise this month?” and you build a new report to find which department caused it.',
                    'after_ar' => 'تضغط على عمود الشهر فينزل إلى الأقسام، ثم إلى البند الذي ارتفع، أمام مديرك في الاجتماع نفسه.',
                    'after_en' => 'You click the month’s bar, drop to the departments and then to the item that rose, in front of your manager in the same meeting.',
                ],
                [
                    'title_ar' => 'تقرير واحد لكل الأقسام',
                    'title_en' => 'One report for every department',
                    'summary_ar' => 'مقسّم العرض (Slicer) زر داخل التقرير يفلتر كل الرسوم حسب القسم أو الشهر بضغطة.',
                    'summary_en' => 'A slicer is a button in the report that filters every chart by department or month with one click.',
                    'now_ar' => 'ترسل لكل مدير قسم نسخة مختلفة من التقرير، وتعدّل النسخ كلها كلما تغيّر رقم.',
                    'now_en' => 'You send each department head a different copy of the report and update them all whenever a number changes.',
                    'after_ar' => 'تقرير واحد فيه مقسّم القسم، كل مدير يضغط على قسمه ويرى أرقامه وحدها.',
                    'after_en' => 'One report with a department slicer: each head clicks their department and sees only their numbers.',
                ],
                [
                    'title_ar' => 'تعرض لوحة معلومات تفاعلية لا شرائح ثابتة',
                    'title_en' => 'Present an interactive dashboard, not static slides',
                    'summary_ar' => 'لوحة المعلومات (Dashboard) صفحة واحدة فيها بطاقات الأرقام ومخططات الأقسام والاتجاه والنسب وزر يعيد الضبط.',
                    'summary_en' => 'A dashboard is one page with number cards, department charts, trends, ratios and a reset button.',
                    'now_ar' => 'قبل كل اجتماع تلصق الرسوم في شرائح عرض، وأي سؤال خارج الشرائح يُؤجَّل للاجتماع القادم.',
                    'now_en' => 'Before every meeting you paste charts into slides, and any question outside them waits for the next meeting.',
                    'after_ar' => 'تفتح اللوحة على جهازك في الاجتماع، وتجيب عن أي سؤال بضغطة على القسم أو الشهر.',
                    'after_en' => 'You open the dashboard on your laptop in the meeting and answer any question with a click on the department or month.',
                ],
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

/**
 * Creates the Excel online landing page on the "landing-excel" template,
 * filled with the content of the approved design (excel-landing/index.html,
 * v1.6, 2026-10-31).
 *
 * php artisan db:seed --class=LandingExcelPageSeeder
 */
class LandingExcelPageSeeder extends LandingCoursePageSeeder
{
    protected const PAGE_SLUG = 'excel';

    protected const COURSE_SLUG = 'advanced-excel';

    protected const IMAGE_DIRECTORY = 'landing-excel';

    protected function pageAttributes(): array
    {
        return [
            'title' => ['ar' => 'الإكسل المحترف عن بعد', 'en' => 'Professional Excel Online'],
            'template' => 'landing-excel',
            'meta_title' => ['ar' => 'الإكسل المحترف عن بعد | مركز مران للتدريب', 'en' => 'Professional Excel Online | Miran Training Center'],
            'meta_description' => [
                'ar' => 'دورة الإكسل المحترف عن بعد من مركز مران للتدريب: 20 ساعة مباشرة في خمس أمسيات، بـ799 ريال. دوال البحث والشروط، الجداول المحورية، ولوحة مؤشرات تفاعلية. سجّل جوالك وسيتصل بك مستشار التدريب.',
                'en' => 'Online professional Excel course by Miran Training Center: 20 live hours over five evenings for SAR 799. Lookups and conditions, PivotTables and an interactive dashboard. Leave your number and a training advisor will call you.',
            ],
        ];
    }

    protected function content(): array
    {
        return [
            'heading' => [
                'ar' => 'طلب مديرك تقريرًا، وأمامك *عشرة آلاف صف؟*',
                'en' => 'Your manager wants a report, and you have *ten thousand rows?*',
            ],
            'subheading' => [
                'ar' => 'خمس أمسيات مباشرة عن بعد من بيتك، تنتقل فيها من إدخال البيانات إلى تحليلها، وتحوّل آلاف الصفوف إلى تقرير جاهز في دقائق.',
                'en' => 'Five live evenings online from home: move from entering data to analysing it, and turn thousands of rows into a ready report in minutes.',
            ],
            'price' => '799',
            'installment' => [
                'ar' => 'أو 3 دفعات × *266.33 ريال* شهريًا بلا رسوم تأخير مع',
                'en' => 'Or 3 payments × *SAR 266.33* a month, no late fees, with',
            ],
            'facts' => [
                ['value_ar' => '20 ساعة', 'value_en' => '20 hours', 'label_ar' => 'تدريب مباشر عن بعد', 'label_en' => 'Live online training'],
                ['value_ar' => '5', 'value_en' => '5', 'label_ar' => 'أمسيات مسائية', 'label_en' => 'Evenings'],
                ['value_ar' => '38', 'value_en' => '38', 'label_ar' => 'تطبيقًا عمليًا', 'label_en' => 'Hands-on exercises'],
            ],
            'tools_title' => ['ar' => 'خمس أدوات *تأخذها لعملك*', 'en' => 'Five tools *you take to work*'],
            'tools' => [
                ['ar' => 'جدول يلخّص آلاف الصفوف', 'en' => 'A table that sums up thousands of rows'],
                ['ar' => 'حسبة تجيب عن سؤال «ماذا لو؟»', 'en' => 'A sheet that answers “what if?”'],
                ['ar' => 'رسوم بيانية تتغيّر بضغطة زر', 'en' => 'Charts that change with one click'],
                ['ar' => 'لوحة مؤشرات تفاعلية لمديرك', 'en' => 'An interactive dashboard for your manager'],
                ['ar' => 'تكتشف أخطاء الجدول قبل أن تصل إلى مديرك', 'en' => 'Catch sheet errors before they reach your manager'],
            ],
            'extras' => [
                ['ar' => 'منصة تعلّم خاصة', 'en' => 'Own learning platform'],
                ['ar' => 'كراسة 38 تطبيقًا', 'en' => 'Workbook of 38 exercises'],
                ['ar' => 'تسجيلات سنتين', 'en' => 'Two years of recordings'],
                ['ar' => 'ورشة تقوية مجانية', 'en' => 'Free refresher workshop'],
                ['ar' => 'شهادة معتمدة', 'en' => 'Accredited certificate'],
            ],
            'stories' => [
                [
                    'title_ar' => 'تجد أي معلومة في ثوانٍ',
                    'title_en' => 'Find any record in seconds',
                    'summary_ar' => 'دالة البحث تجلب لك بيانات موظف أو منتج من جدول آخر تلقائيًا، بدل البحث والنسخ اليدوي.',
                    'summary_en' => 'Lookup functions pull an employee’s or product’s data from another table automatically, instead of searching and copying by hand.',
                    'now_ar' => 'تبحث عن رقم كل موظف في ملف الرواتب وتنسخه يدويًا لمئتي موظف.',
                    'now_en' => 'You look up each employee’s number in the payroll file and copy it by hand, two hundred times.',
                    'after_ar' => 'تكتب معادلة واحدة فيمتلئ العمود كله في ثوانٍ، ويتحدّث وحده عند تغيّر البيانات.',
                    'after_en' => 'One formula fills the whole column in seconds and updates itself when the data changes.',
                ],
                [
                    'title_ar' => 'تجعل الجدول يحكم بنفسه',
                    'title_en' => 'Let the sheet decide for itself',
                    'summary_ar' => 'الشروط تجعل الإكسل يقرّر في كل صف: مكتمل أو متأخر، يستحق أو لا يستحق.',
                    'summary_en' => 'Conditions let Excel decide on every row: complete or late, eligible or not.',
                    'now_ar' => 'تراجع ثلاثمئة عقد واحدًا واحدًا لتعرف أيها يقترب من نهايته.',
                    'now_en' => 'You check three hundred contracts one by one to find which are about to expire.',
                    'after_ar' => 'عمود يكتب أمام كل عقد «ينتهي خلال 30 يومًا» تلقائيًا، ومجموع قيمها في خلية واحدة.',
                    'after_en' => 'A column marks each contract “expires within 30 days” automatically, with their total value in one cell.',
                ],
                [
                    'title_ar' => 'تنظّف البيانات المبعثرة دفعة واحدة',
                    'title_en' => 'Clean messy data in one go',
                    'summary_ar' => 'البيانات المنسوخة من الأنظمة تأتي بمسافات زائدة وأسماء ملتصقة، ودوال النصوص تصلحها كلها معًا.',
                    'summary_en' => 'Data copied from systems comes with extra spaces and joined names; text functions fix it all at once.',
                    'now_ar' => 'تصحّح الأسماء والأرقام خلية خلية قبل أي تقرير.',
                    'now_en' => 'You fix names and numbers cell by cell before every report.',
                    'after_ar' => 'معادلة واحدة تفصل الاسم وتحذف المسافات الزائدة في آلاف الصفوف.',
                    'after_en' => 'One formula splits the name and trims extra spaces across thousands of rows.',
                ],
                [
                    'title_ar' => 'تلخّص آلاف الصفوف في صفحة',
                    'title_en' => 'Sum up thousands of rows on one page',
                    'summary_ar' => 'الجدول المحوري (PivotTable) يجمع البيانات حسب ما تختار: المنطقة أو الشهر أو المنتج.',
                    'summary_en' => 'A PivotTable groups the data however you choose: by region, month or product.',
                    'now_ar' => 'سجل مبيعات السنة عشرة آلاف صف، وسؤال «كم بعنا في كل منطقة؟» يأخذ منك يومًا.',
                    'now_en' => 'The year’s sales log has ten thousand rows, and “how much did we sell per region?” takes you a day.',
                    'after_ar' => 'بضع سحبات بالفأرة، والجواب جاهز في جدول واحد.',
                    'after_en' => 'A few drags of the mouse and the answer is ready in one table.',
                ],
                [
                    'title_ar' => 'تعرض النتائج في لوحة تفاعلية',
                    'title_en' => 'Present results in an interactive dashboard',
                    'summary_ar' => 'لوحة المؤشرات (Dashboard) صفحة رسوم تتغيّر بضغطة على الشهر أو الفرع.',
                    'summary_en' => 'A dashboard is a page of charts that change with a click on the month or branch.',
                    'now_ar' => 'ترسل لمديرك ملفًا مستقلًا لكل فرع، ويقارن بينها بنفسه.',
                    'now_en' => 'You send your manager a separate file per branch and they compare them themselves.',
                    'after_ar' => 'صفحة واحدة يضغط فيها على اسم الفرع، فتتغيّر الرسوم كلها أمامه.',
                    'after_en' => 'One page: they click a branch name and every chart changes in front of them.',
                ],
                [
                    'title_ar' => 'تجيب عن «ماذا لو؟» قبل أن يُسأل',
                    'title_en' => 'Answer “what if?” before it is asked',
                    'summary_ar' => 'أدوات التحليل تحسب أثر تغيير السعر أو التكلفة قبل اتخاذ القرار.',
                    'summary_en' => 'Analysis tools calculate the effect of a price or cost change before the decision is made.',
                    'now_ar' => 'مديرك يسأل: «كم نحتاج أن نبيع لنصل إلى ربح ربع مليون؟» فتجرّب الأرقام يدويًا ساعات.',
                    'now_en' => 'Your manager asks “how much must we sell to reach a quarter-million profit?” and you try numbers by hand for hours.',
                    'after_ar' => 'أداة استهداف الهدف (Goal Seek) تعطيك الكمية المطلوبة في ثوانٍ، ومعها ثلاثة سيناريوهات جاهزة للعرض.',
                    'after_en' => 'Goal Seek gives you the required quantity in seconds, with three scenarios ready to present.',
                ],
            ],
        ];
    }
}

<?php

namespace App\Support;

/**
 * دليل لوحة الإدارة لحصتك — مكتوب كأنك تقرأ دليل تشغيل يخاطبك مباشرة.
 */
final class AdminArchitectureManual
{
    /**
     * @return list<array{title: string, body: string}>
     */
    public static function introPages(): array
    {
        return [
            [
                'title' => 'ازاي تقرأ الدليل ده',
                'body' => "هذا الدليل مكتوب لك وأنت جالس على لوحة إدارة حصتك.\n"
                    ."هدفه مش إنك تحفظ أسماء الجداول — هدفه إنك تعرف: توصل فين، تعمل إيه، ليه القسم موجود، وإيه اللي هيتأثر لو غيّرت حاجة.\n\n"
                    ."حصتك باختصار: دروس خصوصية أونلاين 1:1 + مناهج رسمية + كورسات مستقلة.\n"
                    ."الحضور المعلن للبراند: أمريكا · مصر · السعودية فقط.\n\n"
                    ."طريقة القراءة الموصى بها:\n"
                    ."1) اقرأ خريطة الطبقات مرة واحدة عشان تفهم إن الأدمن فوق، والطالب/المعلم/الموقع تحت.\n"
                    ."2) اقرأ التدفقات الحرجة (توظيف، بيع باقة، كورس، ولي أمر، CRM).\n"
                    ."3) بعد كده افتح أي قسم تحتاجه يومياً — كل قسم مكتوب بنفس الأسلوب: كيف تصل · ماذا يفعل · الهدف · كيف تشتغل · ماذا يتأثر.",
            ],
            [
                'title' => 'ازاي توصل لأي شاشة',
                'body' => "بعد تسجيل الدخول كأدمن أو موظف بصلاحية:\n"
                    ."1) افتح السايدبار من اليمين (اللوحة RTL).\n"
                    ."2) اختَر مجموعة القسم (مثل: التوظيف · الطلاب · المالية · المدفوع).\n"
                    ."3) اضغط اسم الشاشة — لو الزر مش ظاهر، غالباً صلاحيتك ناقصة مش عطل في النظام.\n\n"
                    ."لو ضيعت الطريق: ادخل «مساعد التشغيل» من السايدبار أو ارجع لهذا الملف.\n"
                    ."تحت كل شاشة هتلاقي رابط الصفحة الكامل على الدومين الأساسي — انسخه في المتصفح ويفتح على طول.",
            ],
            [
                'title' => 'طبقات المنصة — ليه يهمك',
                'body' => "تخيّل المنصة أدوار فوق بعض. أنت في الأدمن فوق الكل تقريباً، لكن أثر شغلك ينزل لتحت:\n\n"
                    ."1) لوحة الإدارة — هنا بتضبط السياسات والاعتماد والتسعير والمحتوى والمالية.\n"
                    ."2) الصلاحيات (RBAC) — حتى لو شفت الزر، التنفيذ يتأكد من صلاحيتك. لو موظف مش شايف شاشة: اديله صلاحية، متفترضش إن الصفحة اتكسرت.\n"
                    ."3) النطاق (Services / Models) — منطق الحجز والرصيد والاتفاقيات والطلبات.\n"
                    ."4) لوحات التشغيل — طالب/ولي أمر · معلم · موظف CRM/محاسب. هنا الناس بتشتغل يومياً.\n"
                    ."5) الواجهة العامة — الرئيسية، المعلمين، التسعير، المناهج، الكورسات، الدفع.\n"
                    ."6) التكاملات — البريد، بوابات الدفع، البث، تخزين الملفات.\n\n"
                    ."قاعدة ذهبية لك: أي إعداد في الأدمن لازم يبان أثره في طبقات 4–6. لو مفيش أثر ظاهر، اسأل نفسك: هل الحالة صح؟ (معلم معتمد؟ باقة منشورة؟ بوابة دفع شغالة؟) هل الصلاحية موجودة؟",
            ],
            [
                'title' => 'خريطة التدفقات — امشي وراها خطوة بخطوة',
                'body' => "أ) عايز معلم يظهر للطالب ويتحجز؟\n"
                    ."قدّم من الموقع → راجع في التوظيف → مقابلة/عقد حسب إعداداتك → اعمل اتفاقية أجر → اعتمد ملف المطابقة (مرحلة+مادة+منهج) → يظهر في /instructors ويقدر يتقال.\n\n"
                    ."ب) عايز طالب يشتري حصص 1:1؟\n"
                    ."اضبط سعر الطالب وقواعد الباقة → يظهر في /pricing → الطالب يدفع من /checkout → يتولد Order → يتظبط الرصيد (entitlements) → يحجز حصة → المعلم يكتب التقرير الإلزامي → بعدين تتكلم عن مستحقات المعلم (مسار 1:1 غالباً تسوية/رواتب مش أوتوماتيك لكل حصة).\n\n"
                    ."ج) كورس مستقل:\n"
                    ."أنشئ الكورس ونشره → يظهر في /courses → شراء → تفعيل اشتراك → نسبة المعلم من اتفاقية course_percentage إن وُجدت.\n\n"
                    ."د) ولي أمر:\n"
                    ."حساب parent → ربط أبناء → مرحلة/منهج/مواد → الفلتر في الدليل والباقات يتظبط على بيانات الأبناء.\n\n"
                    ."هـ) CRM:\n"
                    ."Lead → مراحل الـ pipeline → طلب → الدفع النهائي يتأكد من webhook البوابة فقط (مش من رجوع المتصفح).",
            ],
            [
                'title' => 'طبقتان للتسعير — متنخلطش بينهم',
                'body' => "ده من أكتر الغلطات اللي بتحصل يومياً.\n\n"
                    ."طبقة 1 — سعر الطالب (اللي بيظهر في /pricing وcheckout):\n"
                    ."روحك على «أسعار الحصص» + «الباقات» + «قواعد تسعير الباقات».\n"
                    ."لما تغيّر هنا، الطالب يشوف رقم جديد عند الشراء.\n\n"
                    ."طبقة 2 — أجر المعلم (اللي بيتدفع للمعلم):\n"
                    ."روحك على اتفاقيات المعلمين أو عقد التوظيف (salary_per_session / rate / course_percentage).\n"
                    ."لما تغيّر هنا، أنت بتغيّر تكلفة المنصة للمعلم — مش سعر الصفحة العامة.\n\n"
                    ."مهم: مفيش ربط تلقائي. رفعت سعر الباقة للطالب؟ أجر المعلم مش هيتغيّر لوحده.\n"
                    ."الحسبة التلقائية للمستحقات أوضح في نسبة الكورس والاستشارات؛ مسار الحصص 1:1 غالباً يحتاج تسوية رواتب واعية.",
            ],
        ];
    }

    /**
     * كيف توصل لقسم السايدبار — بلغة تشغيل مباشرة.
     *
     * @return array<string, string>
     */
    public static function howToReachBySection(): array
    {
        return [
            'insights' => 'من السايدبار → «ذكاء الأكاديمية» → «تحليلات وتوجهات». ابدأ يومك من هنا قبل أي قسم تفصيلي.',
            'home' => 'من أعلى السايدبار → «الرئيسية»: لوحة التحكم، وارد الإشعارات، والملف الشخصي.',
            'ops' => 'من السايدبار → «التشغيل»: الحصة المجانية ورسائل التواصل — طابور اليوم مع الزائر.',
            'hiring' => 'من السايدبار → «التوظيف»: الطلبات، المقابلات، إعداد البروسيس، ثم المعلمون المفعّلون.',
            'site' => 'من السايدبار → «الموقع»: خدمات، آراء، من نحن، FAQ، وإعدادات ظاهرة للزائر.',
            'students' => 'من السايدبار → «الطلاب»: الحسابات، أولياء الأمور، التسكين، الأرصدة، الحصص، التقارير، الدعم.',
            'sales' => 'من السايدبار → «المبيعات» / CRM: الليدز، الـ pipeline، الطلبات، والعمولات.',
            'finance' => 'من السايدبار → «المالية»: فواتير، محافظ، تقارير، رواتب/حسابات معلمين، مصروفات.',
            'system' => 'من السايدبار → «النظام»: مستخدمون، بوابات دفع، إشعارات، أمان، سجلات.',
            'agreements' => 'من السايدبار → «الاتفاقيات» (أو من بطاقة المعلم في التوظيف): أجر المعلم وعقود الموظفين والسحوبات.',
            'marketing' => 'من السايدبار → «التسويق»: كوبونات، إحالات، نوافذ منبثقة.',
            'paid' => 'من السايدبار → «المدفوع»: أسعار الحصص، الباقات، وقواعد التسعير اللي بتشكّل /pricing.',
            'product' => 'من السايدبار → «المنتج»: مراحل/مواد، ملفات المعلمين، مناهج، كورسات، بث مباشر.',
            'team' => 'من السايدبار → «الفريق» → الموظفون والمدربون: وظائف، مهام، إشراف، إجازات، وطلبات المدربين.',
            'quality' => 'من السايدبار → «الرقابة والجودة»: لوحة الرقابة ثم رقابة الطلاب/المعلمين/الموظفين/العمليات.',
            'more' => 'من السايدبار → «المزيد»: شهادات (إن فُعّلت)، الصلاحيات/الأدوار، الرسائل، والتقارير الشاملة.',
        ];
    }

    /**
     * هدف القسم بكلمات تخاطب القارئ.
     *
     * @return array<string, string>
     */
    public static function purposeBySection(): array
    {
        return [
            'insights' => 'هدف القسم: توصّلك في دقايق لإيه اللي محتاج تدخّلك دلوقتي — مش تقرير للأرشيف.',
            'home' => 'هدف القسم: نقطة دخولك الشخصية؛ تشوف الإشعارات والمهام القصيرة قبل ما تغوص في التفاصيل.',
            'ops' => 'هدف القسم: تمسك أول تماس مع الزائر (رسالة أو حصة تجريبية) وتحوّله لثقة أو بيع.',
            'hiring' => 'هدف القسم: تطلع معلم معتمد جاهز للمطابقة والحجز — من غير اختصار على الجودة أو العقد.',
            'site' => 'هدف القسم: تظبط كلام وثقة الموقع العام بما يطابق براند حصتك.',
            'students' => 'هدف القسم: تدير دورة حياة الطالب من الحساب والرصيد لحد الحصة والتقرير والدعم.',
            'sales' => 'هدف القسم: تحرّك الفرصة من lead لطلب مدفوع مؤكد — مع عمولات الفريق لو مفعّلة.',
            'finance' => 'هدف القسم: تتابع الفلوس صح: دفع الطالب، فواتير، محافظ، ومستحقات/رواتب المعلمين.',
            'system' => 'هدف القسم: تشغّل البنية التحتية بأمان: بوابات، إشعارات، مستخدمين، وسجلات.',
            'agreements' => 'هدف القسم: تثبّت «بكم بندفع للمعلم/الموظف» وطلبات السحب — منفصل عن سعر الطالب.',
            'marketing' => 'هدف القسم: تزود التحويل بخصم أو إحالة أو رسالة ظهور بدون تخريب التسعير الأساسي.',
            'paid' => 'هدف القسم: تبني كتالوج الأسعار والباقات اللي الطالب يشوفه ويدفع عليه.',
            'product' => 'هدف القسم: تجهّز العرض الأكاديمي العام — معلمون، مناهج، كورسات، وجلسات مباشرة.',
            'team' => 'هدف القسم: تشغّل الموظفين والمهام والإشراف الأكاديمي وطلبات المدربين والإجازات.',
            'quality' => 'هدف القسم: تكتشف الخلل التشغيلي مبكراً على الطلاب والمعلمين والفريق قبل ما يكبر.',
            'more' => 'هدف القسم: الشهادات والصلاحيات والرسائل والتقارير الشاملة — حسب ما ظاهر في سايدبارك.',
        ];
    }

    /**
     * تأثير كل قسم — بصيغة «لما تشتغل هنا…».
     *
     * @return array<string, array{public: string, student: string, teacher: string, data: string}>
     */
    public static function layerImpactBySection(): array
    {
        return [
            'insights' => [
                'public' => 'الزائر مش هيشوف الشاشة دي. أثرها إنك تتدخل أسرع فتجربة الموقع تتحسن.',
                'student' => 'لما تقفل اختناق (تقرير/دعم)، الطالب يحس بسرعة الاستجابة.',
                'teacher' => 'لما تتابع التقارير المتأخرة، المعلم يتلحق قبل ما الأجر يتعلق.',
                'data' => 'قراءات من الحجوزات والتقارير والطلبات — القسم ده بيقرأ أكتر ما بيكتب سياسات.',
            ],
            'home' => [
                'public' => 'مفيش أثر مباشر على الصفحة العامة.',
                'student' => 'أثر غير مباشر: فريقك بيرد أسرع لما الإشعارات متتابعة.',
                'teacher' => 'نفس الفكرة — سرعة متابعة داخلية.',
                'data' => 'إشعارات وملف الموظف اللي فاتح اللوحة.',
            ],
            'ops' => [
                'public' => 'نماذج التواصل والحصة التجريبية على الموقع بتوصل لهنا.',
                'student' => 'أول تجربة مجانية كويسة = فرصة تحويل لباقة.',
                'teacher' => 'ممكن يتسند معلم للتجربة حسب تشغيلكم.',
                'data' => 'حجوزات تجريبية ورسائل تواصل — غالباً بتتحول لـ CRM leads.',
            ],
            'hiring' => [
                'public' => 'نموذج التقديم على /for-teachers؛ وبعد الاعتماد المعلم يظهر في الدليل.',
                'student' => 'كل معلم معتمد = خيار حجز جديد للطالب.',
                'teacher' => 'هنا بيتحدد مصيره: مراجعة، عقد، أجر، وتفعيل.',
                'data' => 'طلبات التوظيف، الاتفاقيات، وملفات المطابقة.',
            ],
            'site' => [
                'public' => 'من نحن، FAQ، الآراء، وخدمات الموقع — كلام الزائر بيشوفه.',
                'student' => 'ثقة أوضح قبل الدفع.',
                'teacher' => 'نبرة صفحة الانضمام والسياسات العامة.',
                'data' => 'محتوى الموقع والإعدادات الظاهرة.',
            ],
            'students' => [
                'public' => 'مش واجهة عامة، لكن التسكين والأرصدة الغلط تبوّظ الحجز والدليل.',
                'student' => 'هنا بتأثر على حسابه، أبنائه، رصيده، حصصه، تذاكره، وتقاريره.',
                'teacher' => 'جدول 1:1 والتقارير الإلزامية مرتبطين بما هنا.',
                'data' => 'المستخدمون، الأرصدة، الجلسات، وتذاكر الدعم.',
            ],
            'sales' => [
                'public' => 'مش واجهة بيع عامة؛ السرعة هنا بتخلّص الطلبات أسرع.',
                'student' => 'اعتماد الدفع/الطلب يفعّل اشتراكه أو رصيده.',
                'teacher' => 'عمولات CRM للمسوقين — مش أجر المعلم.',
                'data' => 'الليدز، الطلبات، والعمولات.',
            ],
            'finance' => [
                'public' => 'بوابات الدفع اللي بتظهر في صفحة الدفع.',
                'student' => 'فواتير، محافظ، وتقسيط إن وُجد.',
                'teacher' => 'رواتب/حسابات وسحوبات — فلوس المعلم بتعدي من هنا.',
                'data' => 'طلبات، فواتير، محافظ، مدفوعات اتفاقيات، ومصروفات.',
            ],
            'system' => [
                'public' => 'تشغيل البوابات والإشعارات العامة.',
                'student' => 'إيميلات وتنبيهات اللوحة.',
                'teacher' => 'نفس قنوات التنبيه + أمان الحساب.',
                'data' => 'المستخدمون، الإعدادات، سجلات النشاط، وإعدادات البوابات.',
            ],
            'agreements' => [
                'public' => 'مش ظاهرة للزائر.',
                'student' => 'أثر غير مباشر: معلمين ثابتين ومستمرين.',
                'teacher' => 'عقد الأجر وطلبات السحب — دي محفظته النظامية.',
                'data' => 'اتفاقيات معلمين/موظفين وطلبات السحب.',
            ],
            'marketing' => [
                'public' => 'كوبونات، إحالات، ونوافذ منبثقة على الموقع.',
                'student' => 'خصم أو رصيد عند الدفع لو الكود شغّال.',
                'teacher' => 'عادة مفيش أثر مباشر على أجره.',
                'data' => 'كوبونات، إحالات، وإعلانات منبثقة.',
            ],
            'paid' => [
                'public' => 'صفحة /pricing وكتالوج الباقات بيتبني من هنا.',
                'student' => 'بيشتري رصيد حصص 1:1 بالأسعار اللي ضبطتها.',
                'teacher' => 'يزيد الطلب على الحصص — من غير ما يغيّر أجره لوحده.',
                'data' => 'الباقات، قواعد التسعير، أسعار الحصص، والأرصدة بعد الشراء.',
            ],
            'product' => [
                'public' => '/instructors و /curricula و /courses والبث.',
                'student' => 'يشوف ويحجز ويتفرج حسب ما نشرت واعتمدت.',
                'teacher' => 'ملف المطابقة، التوافر، المحتوى، والجلسات.',
                'data' => 'المراحل/المواد، ملفات المعلمين، الكورسات، والجلسات المباشرة.',
            ],
            'team' => [
                'public' => 'مفيش واجهة عامة.',
                'student' => 'جودة الخدمة تعتمد على مين عنده صلاحية يرد ويعتمد.',
                'teacher' => 'مهام المدربين وطلباتهم وإشرافهم بيتداروا من هنا.',
                'data' => 'الموظفون، الوظائف، المهام، الإجازات، وطلبات المدربين.',
            ],
            'quality' => [
                'public' => 'مش واجهة عامة؛ أثرها تحسين الجودة اللي الزائر/الطالب بيحسّها.',
                'student' => 'متابعة مشاكل المسار والجودة قبل ما تتحول لتذكرة دعم.',
                'teacher' => 'رقابة الالتزام والتقارير والأداء.',
                'data' => 'لوحات رقابة وقراءات تشغيلية عبر الأقسام.',
            ],
            'more' => [
                'public' => 'الشهادات قد تظهر للطالب إن كانت مفعّلة؛ التقارير داخلية.',
                'student' => 'شهادات/تقارير تؤثر على تجربته حسب التفعيل.',
                'teacher' => 'عادة غير مباشر إلا عبر جودة التشغيل والصلاحيات.',
                'data' => 'أدوار، صلاحيات، شهادات، رسائل، وتقارير.',
            ],
        ];
    }

    /**
     * @return list<array{title: string, body: string}>
     */
    public static function closingPages(): array
    {
        return [
            [
                'title' => 'روتينك اليومي كمدير',
                'body' => "اشتغل بالترتيب ده كل صباح (أو كل وردية):\n\n"
                    ."1) افتح ذكاء الأكاديمية — ركّز على الأحمر/العاجل.\n"
                    ."2) راجع طلبات الدفع المعلّقة وتأكد إن webhook البوابة شغال.\n"
                    ."3) طابور تقارير 1:1 المتأخرة — الحق المعلم قبل ما الأجر يتلخبط.\n"
                    ."4) تذاكر الدعم المفتوحة — اقفل أو حوّل بوضوح.\n"
                    ."5) طلبات توظيف مستنية قرار — متسيبهاش تكبر.\n"
                    ."6) سحوبات معلمين جاهزة بعد التحقق — ادفع اللي اتأكد.\n\n"
                    ."لو خلصت الروتين ده، باقي اليوم يبقى تحسين مش إطفاء حرايق.",
            ],
            [
                'title' => 'لو حاجة «مش شغالة» — ابدأ من هنا',
                'body' => "• دليل المعلمين فاضي وفي حسابات؟\n"
                    ."  غالباً الملف مش معتمد أو المطابقة ناقصة، أو محتوى تجريبي محجوب عن العام.\n\n"
                    ."• فلتر المرحلة بيرجع ولا حاجة؟\n"
                    ."  اتأكد من ربط المعلم بالمرحلة والمواد (مش كفاية وجود حساب).\n\n"
                    ."• الدفع بيرمي على واتساب؟\n"
                    ."  بوابة Stripe/فواتيرك مش مفعّلة — هيفضل التحويل البنكي اليدوي لحد ما تشغّل بوابة.\n\n"
                    ."• غيّرت سعر الطالب والأجر ثابت؟\n"
                    ."  صح كده: طبقتان منفصلتان. غيّر الاتفاقية لو عايز تكلفة المعلم تتغير.\n\n"
                    ."• موظف مش شايف زر؟\n"
                    ."  صلاحية RBAC — مش عطل. اديله الإذن من قسم الفريق.",
            ],
            [
                'title' => 'اختصارات توصل بسرعة',
                'body' => "أسعار حصص الطالب → السايدبار «المدفوع» → سعر الحصة (مرحلة×مسار)\n"
                    ."باقات 1:1 → المدفوع → باقات الحصص\n"
                    ."قواعد تسعير الباقات → المدفوع → قواعد التسعير\n"
                    ."أجر المعلم → الاتفاقيات أو عقد التوظيف\n"
                    ."اعتماد ملفات المعلمين → التوظيف / البراند الشخصي\n"
                    ."المراحل والمواد → المنتج → السنوات/المواد الدراسية\n"
                    ."الطلبات والمدفوعات → المبيعات + المالية + بوابات الدفع في النظام\n"
                    ."مساعد التشغيل داخل اللوحة → من السايدبار أو رابط الصفحة في هذا الدليل\n\n"
                    ."تحت كل شاشة هتلاقي «رابط الصفحة» كامل على الدومين الأساسي — انسخه أو اضغطه وافتح على طول.",
            ],
        ];
    }

    /**
     * الدومين الظاهر في روابط الدليل (يفضّل الإنتاج وليس localhost).
     */
    public static function resolvePublicBaseUrl(?string $override = null): string
    {
        if (is_string($override) && trim($override) !== '') {
            return rtrim(trim($override), '/');
        }

        $configured = rtrim((string) config('app.url'), '/');
        $host = (string) (parse_url($configured, PHP_URL_HOST) ?: '');
        $localHosts = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];

        if ($configured === '' || $host === '' || in_array($host, $localHosts, true)) {
            return 'https://hissatak.online';
        }

        return $configured;
    }

    /**
     * معاملات افتراضية لمسارات تحتاج باراميتر ظاهر في السايدبار.
     *
     * @return array<string, array<string, string>>
     */
    public static function defaultRouteParameters(): array
    {
        return [
            'admin.email-broadcasts.index' => ['audience' => 'all_users'],
        ];
    }

    /**
     * رابط صفحة كامل من اسم المسار — للبحث/الفتح مباشرة من الدليل.
     */
    public static function absolutePageUrl(string $routeName, ?string $baseUrl = null): ?string
    {
        if ($routeName === '' || ! \Illuminate\Support\Facades\Route::has($routeName)) {
            return null;
        }

        $parameters = self::defaultRouteParameters()[$routeName] ?? [];

        try {
            $relative = route($routeName, $parameters, absolute: false);
        } catch (\Throwable) {
            return null;
        }

        $path = parse_url($relative, PHP_URL_PATH);
        if (! is_string($path) || $path === '') {
            $path = '/'.ltrim($relative, '/');
        }

        return self::resolvePublicBaseUrl($baseUrl).$path;
    }

    /**
     * HTML كامل للطباعة/PDF عبر Chrome (RTL + خطوط عربية).
     */
    public static function toPrintHtml(string $fontFaceCss = '', ?string $publicBaseUrl = null): string
    {
        $generatedAt = now()->timezone(config('app.timezone', 'UTC'))->format('Y-m-d H:i');
        $baseUrl = self::resolvePublicBaseUrl($publicBaseUrl);
        $sections = AdminOpsGuideCatalog::sections();
        $impacts = self::layerImpactBySection();
        $howToReach = self::howToReachBySection();
        $purposes = self::purposeBySection();

        $html = '<!DOCTYPE html><html lang="ar" dir="rtl"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>دليل لوحة الإدارة — حصتك</title><style>'
            .$fontFaceCss
            .'
            @page { size: A4; margin: 16mm 14mm; }
            * { box-sizing: border-box; }
            body{
              font-family: "NotoNaskhArabic", "Amiri", "IBM Plex Sans Arabic", "Segoe UI", Tahoma, sans-serif;
              font-size: 12pt;
              line-height: 1.95;
              color: #152A4A;
              direction: rtl;
              text-align: right;
              margin: 0;
              padding: 0;
              -webkit-font-smoothing: antialiased;
              font-kerning: normal;
              font-variant-ligatures: common-ligatures;
              text-rendering: optimizeLegibility;
            }
            h1,h2,h3,.cover .brand{
              font-family: "Amiri", "NotoNaskhArabic", serif;
              font-feature-settings: "kern" 1, "liga" 1, "calt" 1;
            }
            h1{font-size:22pt;color:#1E4E8C;margin:0 0 10px;font-weight:700}
            h2{font-size:16pt;color:#1E4E8C;margin:0 0 10px;padding-bottom:6px;border-bottom:2px solid #C9952A;font-weight:700;page-break-after:avoid}
            h3{font-size:13pt;color:#152A4A;margin:14px 0 6px;font-weight:700;page-break-after:avoid}
            p,li,div{orphans:3;widows:3}
            .cover{text-align:center;padding:48px 16px 22px}
            .cover .brand{font-size:36pt;font-weight:700;color:#1E4E8C;letter-spacing:0;line-height:1.3}
            .cover .sub{color:#5b6b7c;margin-top:10px;font-size:13pt;font-family:"NotoNaskhArabic","Amiri",sans-serif}
            .meta{font-size:10.5pt;color:#5b6b7c;margin:6px 0 14px}
            .box{background:#f5f8fc;border:1px solid #d7e3f0;border-radius:10px;padding:12px 14px;margin:10px 0}
            .callout{background:#fff8eb;border:1px solid #e8d3a2;border-radius:10px;padding:12px 14px;margin:10px 0}
            .item{margin:10px 0;padding:10px 12px;border-right:4px solid #1E4E8C;background:#fff;border-radius:0 8px 8px 0;page-break-inside:avoid}
            .tip{color:#6b4f1d;font-size:10.5pt;margin-top:4px}
            .layer{font-size:11pt;margin:5px 0}
            .layer b{color:#1E4E8C}
            .label{color:#1E4E8C;font-weight:700}
            .section{page-break-before:always;padding-top:4px}
            .section:first-of-type{page-break-before:auto}
            ul{margin:6px 0 10px;padding-right:22px}
            li{margin:3px 0}
            .foot{font-size:9.5pt;color:#8896a6;margin-top:28px;text-align:center}
            .page-link{font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size:9.5pt; color:#1E4E8C; direction:ltr; unicode-bidi:embed; text-decoration:underline; word-break:break-all}
            .page-link-wrap{margin:4px 0 6px; font-size:10.5pt}
        </style></head><body>';

        $html .= '<div class="cover">'
            .'<div class="brand">حصتك</div>'
            .'<h1>دليل لوحة الإدارة الكامل</h1>'
            .'<div class="sub">ازاي توصل · ازاي تشتغل · القسم بيعمل إيه · وإيه الهدف منه</div>'
            .'<div class="meta">توليد آلي من النظام · '.$generatedAt.' · روابط الصفحات على '.e($baseUrl).'</div>'
            .'</div>';

        foreach (self::introPages() as $i => $page) {
            $html .= '<section class="section"'.($i === 0 ? ' style="page-break-before:auto"' : '').'>';
            $html .= '<h2>'.e($page['title']).'</h2>';
            $html .= '<div class="box">'.nl2br(e($page['body'])).'</div>';
            $html .= '</section>';
        }

        foreach ($sections as $section) {
            $id = $section['id'];
            $html .= '<section class="section">';
            $html .= '<h2>'.e($section['title']).'</h2>';
            $html .= '<div class="meta">لِمين القسم ده؟ '.e($section['audience']).'</div>';

            if (! empty($howToReach[$id])) {
                $html .= '<div class="callout"><span class="label">ازاي توصل:</span> '.e($howToReach[$id]).'</div>';
            }

            $html .= '<div class="box">';
            if (! empty($purposes[$id])) {
                $html .= '<div style="margin-bottom:8px"><span class="label">الهدف:</span> '.e($purposes[$id]).'</div>';
            }
            $html .= '<div style="margin-bottom:8px"><span class="label">القسم بيعمل إيه:</span> '.e($section['summary']).'</div>';
            $html .= '<div><span class="label">قرار تشغيلي لك:</span> '.e($section['decision']).'</div>';
            $html .= '</div>';

            if (! empty($section['steps'])) {
                $html .= '<h3>ازاي تشتغل بيه (خطوة بخطوة)</h3><ul>';
                foreach ($section['steps'] as $step) {
                    $html .= '<li>'.e($step).'</li>';
                }
                $html .= '</ul>';
            }

            $impact = $impacts[$id] ?? null;
            if ($impact) {
                $html .= '<h3>لما تشتغل هنا… إيه اللي بيحصل؟</h3><div class="box">';
                $html .= '<div class="layer"><b>على الموقع العام:</b> '.e($impact['public']).'</div>';
                $html .= '<div class="layer"><b>على الطالب / ولي الأمر:</b> '.e($impact['student']).'</div>';
                $html .= '<div class="layer"><b>على المعلم:</b> '.e($impact['teacher']).'</div>';
                $html .= '<div class="layer"><b>على البيانات من تحت:</b> '.e($impact['data']).'</div>';
                $html .= '</div>';
            }

            $html .= '<h3>الشاشات جوه القسم — افتحها واحدة واحدة</h3>';
            foreach ($section['items'] as $item) {
                $html .= '<div class="item">';
                $html .= '<b>'.e($item['title']).'</b>';
                if (! empty($item['route'])) {
                    $pageUrl = self::absolutePageUrl((string) $item['route'], $baseUrl);
                    if ($pageUrl) {
                        $html .= '<div class="page-link-wrap"><span class="label">رابط الصفحة:</span> '
                            .'<a class="page-link" href="'.e($pageUrl).'">'.e($pageUrl).'</a></div>';
                    }
                }
                $html .= '<div><span class="label">بتعمل إيه:</span> '.e($item['body']).'</div>';
                if (! empty($item['tips'])) {
                    $html .= '<div class="tip">نصيحة عملية: '.e(implode(' · ', $item['tips'])).'</div>';
                }
                $html .= '</div>';
            }
            $html .= '</section>';
        }

        foreach (self::closingPages() as $page) {
            $html .= '<section class="section">';
            $html .= '<h2>'.e($page['title']).'</h2>';
            $html .= '<div class="box">'.nl2br(e($page['body'])).'</div>';
            $html .= '</section>';
        }

        $html .= '<div class="foot">حصتك · دليل تشغيلي داخلي يخاطب فريق الإدارة · لا يُنشر للعامة</div>';
        $html .= '</body></html>';

        return $html;
    }

    /** @deprecated استخدم toPrintHtml */
    public static function toHtml(): string
    {
        return self::toPrintHtml();
    }
}

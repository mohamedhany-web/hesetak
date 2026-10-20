#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
تقرير إصلاحات حصتك (PDF عربي — غير تقني)

الخطوط: Amiri للعناوين + Noto Naskh للنصوص (مضمّنة في resources/fonts).

الاستخدام:
  python3 scripts/generate_hesetak_fix_report.py
  python3 scripts/generate_hesetak_fix_report.py --out reports/تقرير.pdf
"""

from __future__ import annotations

import argparse
from datetime import datetime
from pathlib import Path

import arabic_reshaper
from bidi.algorithm import get_display
from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_RIGHT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import cm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    HRFlowable,
    KeepTogether,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)

ROOT = Path(__file__).resolve().parents[1]
FONTS_DIR = ROOT / "resources" / "fonts"

REPORT_META = {
    "title": "تقرير إصلاحات المنصة",
    "product": "حصتك",
    "subtitle": "ملخص الأعطال الظاهرة والإجراءات المنجزة — 28 و 29 سبتمبر 2026",
    "audience": "للإدارة ومالكي المنتج — صياغة تنفيذية بدون تفاصيل تقنية عميقة",
    "period": "أمس (28 سبتمبر) + اليوم (29 سبتمبر) 2026",
}

# كل الأعطال/الطلبات التي أُغلقت أمس واليوم (من سجل العمل)
ISSUES = [
    # ——— أمس ———
    {
        "day": "أمس — 28 سبتمبر",
        "id": "أ-1",
        "title": "ملف robots.txt يعيد صفحة خطأ بدل تعليمات محركات البحث",
        "pages": "https://hissatak.online/robots.txt",
        "problem": "الرابط كان يعرض صفحة خطأ HTML بدل ملف نصي نظيف، فيؤثر على فهرسة محركات البحث وتوجيهها بعيداً عن صفحات الدخول والإدارة.",
        "fix": "توفير ملف نصي صحيح يمنع فهرسة صفحات الدخول والتسجيل ولوحات التحكم ومسارات الدفع الحساسة.",
        "result": "الرابط يعيد ملفاً نصياً سليماً بحالة نجاح، بدون صفحة خطأ.",
        "priority": "متوسط",
    },
    {
        "day": "أمس — 28 سبتمبر",
        "id": "أ-2",
        "title": "إزالة أي أثر للعلامة القديمة (Glottical) من المنصة",
        "pages": "الواجهة العامة · الإعدادات · المسارات والملفات الظاهرة",
        "problem": "بقيت إشارات ومسارات وأسماء مرتبطة بالاسم القديم للمنصة، رغم أن الهوية الرسمية أصبحت «حصتك».",
        "fix": "مراجعة شاملة وإزالة/استبدال كل ما يظهر للمستخدم أو في الإعدادات الحية باسم أو مسار العلامة القديمة.",
        "result": "لا يظهر أي أثر للعلامة القديمة على الموقع الحي؛ الهوية المعروضة حصتك فقط.",
        "priority": "مرتفع",
    },
    # ——— اليوم ———
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "1",
        "title": "صلاحيات الموظف تظهر أوسع مما مُنح له",
        "pages": "لوحة التحكم · صلاحيات الموظف · توظيف المعلمين › الإعدادات",
        "problem": "موظف بصلاحيات محدودة (مثل المبيعات) كان يرى لوحة إدارة شبه كاملة وأرقاماً مالية، بينما صفحات ممنوحة له صراحةً قد تظهر «غير مصرح».",
        "fix": "توحيد مصدر الصلاحيات الفعلية، تقييد قسم المبيعات على مساحته، إخفاء الأرقام والروابط غير المصرح بها، ومراجعة تطابق القائمة مع ما يُسمح به فعلاً.",
        "result": "الموظف المحدود يرى ما يخصه فقط؛ وحسابات الإدارة الكاملة لم تتأثر سلباً.",
        "priority": "حرج",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "4",
        "title": "ظهور رمز الدولار بينما التسعير بالريال",
        "pages": "الكوبونات · رصيد محفظة الطالب · اتفاقيات المدربين · بطاقات الكورسات · الملفات التعريفية",
        "problem": "شاشات كثيرة تعرض علامة دولار بجانب مبالغ يفترض أنها بالريال السعودي، فيخلط الرموز ويضعف الثقة.",
        "fix": "توحيد العملة المعروضة على الريال (ر.س) عبر دالة تنسيق واحدة وأيقونة محايدة، مع مراجعة الشاشات الإدارية والعامة.",
        "result": "العرض موحّد بالريال السعودي؛ لا تظهر علامة الدولار كعملة في القوالب المفحوصة.",
        "priority": "مرتفع",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "5",
        "title": "ملفات معلمين «معتمدة» رغم نقص المطابقة أو محتوى تجريبي",
        "pages": "التسويق › الملفات التعريفية للمعلمين",
        "problem": "ملفات عليها وسم «معتمد» مع تنبيه «ناقص مطابقة»، وبعض العناوين التجريبية وُلدت منشورة — شرط الجودة كان على زر واحد فقط.",
        "fix": "فرض شرط الاكتمال عند أي اعتماد/نشر، أدوات تدقيق في القائمة، إيقاف نشر الملفات الناقصة التجريبية، ومنع ظهورها في الدليل العام.",
        "result": "لا يبقى ملف معتمد ناقص على السيرفر؛ الاعتماد يمر بنفس معايير الجودة دائماً.",
        "priority": "مرتفع",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "6",
        "title": "بوابات الدفع: لا بوابة إلكترونية مفعّلة + الحاجة لـ Stripe",
        "pages": "مركز بوابات الدفع",
        "problem": "كل البوابات موقوفة فلا دفع إلكتروني للطلاب، مع طلب استبدال PayPal بـ Stripe مع الإبقاء على خيار الدفع اليدوي عند الحاجة.",
        "fix": "إضافة Stripe لمسار الدفع، أرشفة PayPal للسجلات فقط، وخيار السماح بالدفع اليدوي مع البوابات (تحويل/فودافون كاش).",
        "result": "مركز البوابات جاهز لتفعيل Stripe مع الريال، مع مرونة الدفع اليدوي عند التجهيز البنكي.",
        "priority": "حرج",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "7",
        "title": "برنامج الإحالات نشط بلا حدود آمنة",
        "pages": "إدارة برنامج الإحالات",
        "problem": "الحدود فارغة والبرنامج يعمل بلا سقف واضح مع إمكانية إحالة ذاتية — خطر إساءة استخدام.",
        "fix": "حد افتراضي معقول لكل مستخدم، تأكيد صريح لـ«غير محدود»، منع الإحالة الذاتية وتكرار الجوال/الجهاز، وملء رسالة واتساب الإنجليزية تلقائياً عند الحاجة.",
        "result": "البرنامج يعمل بحدود أوضح ومكافحة إساءة استخدام أساسية.",
        "priority": "مرتفع",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "8",
        "title": "اتفاقية موظف تُحفظ فارغة بلا شروط أو بنود",
        "pages": "اتفاقيات الموظفين",
        "problem": "يمكن حفظ اتفاقية بلا وصف ولا شروط ولا بنود وبراتب صفر — عقد بلا قيمة تشغيلية أو قانونية، مع غياب مسار توقيع واضح.",
        "fix": "إلزام الحقول الجوهرية عند التفعيل، قالب عربي جاهز، توليد مستند قابل للمعاينة، توقيع برمز تحقق، وسجل إصدارات بعد التوقيع.",
        "result": "لا تُفعَّل اتفاقية فارغة؛ يوجد قالب وتوقيع ومتابعة إصدارات.",
        "priority": "مرتفع",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "9",
        "title": "خطأ إملائي: «خصص» بدل «حصص» في التسعير",
        "pages": "تسعير الباقات / القائمة الجانبية",
        "problem": "ظهرت كلمة «خصص» في عنوان الصفحة والقائمة ووصف القواعد والمقصود «حصص».",
        "fix": "تصحيح النصوص من ملفات الترجمة في المواضع الخاصة بالحصص فقط.",
        "result": "العبارات تظهر بالصيغة الصحيحة المرتبطة بالحصص.",
        "priority": "منخفض",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "10",
        "title": "إعادة تصميم صفحة الباقات العامة (تجربة اختيار أوضح)",
        "pages": "صفحة الباقات / التسعير على الموقع",
        "problem": "صفحة الباقات لم تكن تسهّل اختيار المرحلة والمادة والأسعار بطريقة واضحة للأسرة.",
        "fix": "بناء تجربة بمراحل ومواد وبطاقات أسعار (باقة أكبر / أصغر / مستعجلة) بهوية حصتك، مع ربط «باقة متنوعة» لأداة التخصيص.",
        "result": "صفحة الباقات أوضح على الجوال وسطح المكتب وتتماشى مع أسلوب مواقع تعليمية مألوفة.",
        "priority": "مرتفع",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "10-ب",
        "title": "فلاتر الباقات وتوصيف الباقة من الإدارة",
        "pages": "صفحة الباقات العامة + إدارة باقات الخدمات",
        "problem": "يلزم التأكد أن المرحلة والمادة تغيّران البطاقات فعلياً، وأن الأدمن يستطيع توصيف الباقة لتظهر على بطاقة المادة.",
        "fix": "التحقق من الفلاتر، وتوضيح ربط المرحلة+المادة لاسم ووصف وشارة البطاقة، مع الاعتماد على قواعد التسعير للأسعار.",
        "result": "الفلاتر تعمل؛ والإدارة تعرف كيف توصّف الباقة للزائر.",
        "priority": "متوسط",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "11",
        "title": "دليل مستخدمين موحّد للأنواع الأربعة",
        "pages": "إدارة النظام › المستخدمين",
        "problem": "الطلاب وأولياء الأمور والمعلمون والموظفون موزّعون على صفحات منفصلة بدون نظرة واحدة مع بحث وتصدير.",
        "fix": "صفحة واحدة بتبويبات وعدّادات، بحث وفلترة وتصدير، أعمدة حسب النوع، وإجراءات تشغيلية مع حماية خصوصية جهات الاتصال.",
        "result": "الإدارة تدير الأنواع الأربعة من مكان واحد بأدوات يومية أوضح.",
        "priority": "مرتفع",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "13",
        "title": "زر تحسين قاعدة البيانات بلا أثر ظاهر + تعطيل صفحة الأداء",
        "pages": "إدارة النظام › أداء الموقع",
        "problem": "بعد التحسين لا يظهر فرق سرعة فيبدو الزر بلا فائدة؛ و«تحسين الأداء» كان يتعطل بسبب تعارض تسمية مسارات.",
        "fix": "عرض نتيجة قبل/بعد (حجم، جداول، مساحة مسترجعة، مدة، آخر تشغيل) مع تنبيه أوقات الذروة، وإصلاح التعارض الذي كان يعطّل التحسين.",
        "result": "الزر يعرض نتيجة مفهومة؛ وشرح أن غياب فرق السرعة طبيعي على قاعدة صغيرة؛ والتحسين العام يعمل مجدداً.",
        "priority": "منخفض",
    },
    {
        "day": "اليوم — 29 سبتمبر",
        "id": "14",
        "title": "رسائل النجاح والتنبيه تظهر مرتين",
        "pages": "إضافة رصيد لمحفظة الطالب وصفحات إدارية أخرى",
        "problem": "نفس الرسالة تظهر مرتين (أعلى الصفحة وداخل المحتوى) فتبدو كخلل.",
        "fix": "توحيد عرض التنبيه في قالب اللوحة مرة واحدة فقط.",
        "result": "رسالة النجاح أو التنبيه تظهر مرة واحدة بشكل موحّد.",
        "priority": "منخفض",
    },
]

NOTES = [
    "التقرير يغطي ما أُنجز أمس واليوم فقط حسب سجل العمل على منصة حصتك.",
    "البنود ٢ و٣ و١٢ لم تُرفع كبلاغات منفصلة في نفس الدورة؛ إن وُجدت لاحقاً تُضاف في تقرير تالٍ.",
    "بعض التحسينات التقنية (مثل تحسين القاعدة) لا تغيّر سرعة الموقع بشكل ملحوظ على بيانات صغيرة — وهذا متوقع.",
    "يُفضَّل مرور سريع على: الباقات، دليل المستخدمين، بوابات الدفع، الملفات التعريفية، وصفحة الأداء بعد كل نشر.",
]


def ar(text: str) -> str:
    if not text:
        return ""
    eastern = "٠١٢٣٤٥٦٧٨٩"
    western = "0123456789"
    text = str(text).translate(str.maketrans(eastern, western))
    # رموز تُربك بعض خطوط PDF
    for bad, good in {
        "\u2014": "-",  # —
        "\u2013": "-",  # –
        "\u00a0": " ",
        "\u200f": "",
        "\u200e": "",
        "\ufeff": "",
        "«": '"',
        "»": '"',
        "·": "-",
    }.items():
        text = text.replace(bad, good)
    return get_display(arabic_reshaper.reshape(text))


def register_fonts() -> tuple[str, str]:
    """يسجّل Amiri للعناوين و Noto Naskh للمتن."""
    amiri_r = FONTS_DIR / "Amiri-Regular.ttf"
    amiri_b = FONTS_DIR / "Amiri-Bold.ttf"
    noto_r = FONTS_DIR / "NotoNaskhArabic-Regular.ttf"
    noto_b = FONTS_DIR / "NotoNaskhArabic-Bold.ttf"
    system = Path("/Library/Fonts/Arial Unicode.ttf")

    body, head = "HesetakBody", "HesetakHead"

    if not noto_r.exists() and not amiri_r.exists() and not system.exists():
        raise FileNotFoundError(
            f"لا يوجد خط عربي. ضع Noto/Amiri في {FONTS_DIR} أو ثبّت Arial Unicode."
        )

    body_regular = noto_r if noto_r.exists() else (amiri_r if amiri_r.exists() else system)
    body_bold = noto_b if noto_b.exists() else (amiri_b if amiri_b.exists() else body_regular)
    head_regular = amiri_r if amiri_r.exists() else body_regular
    head_bold = amiri_b if amiri_b.exists() else body_bold

    pdfmetrics.registerFont(TTFont(body, str(body_regular)))
    pdfmetrics.registerFont(TTFont(f"{body}-Bold", str(body_bold)))
    pdfmetrics.registerFont(TTFont(head, str(head_regular)))
    pdfmetrics.registerFont(TTFont(f"{head}-Bold", str(head_bold)))

    return head, body


def build_styles(head: str, body: str) -> dict:
    base = getSampleStyleSheet()
    navy = colors.HexColor("#1E4E8C")
    gold = colors.HexColor("#C9952A")
    ink = colors.HexColor("#0f172a")
    muted = colors.HexColor("#64748b")

    return {
        "cover_title": ParagraphStyle(
            "cover_title", parent=base["Title"], fontName=f"{head}-Bold",
            fontSize=24, leading=36, alignment=TA_CENTER, textColor=navy, spaceAfter=10,
        ),
        "cover_sub": ParagraphStyle(
            "cover_sub", parent=base["Normal"], fontName=body,
            fontSize=12, leading=22, alignment=TA_CENTER, textColor=colors.HexColor("#334155"), spaceAfter=6,
        ),
        "h1": ParagraphStyle(
            "h1", parent=base["Heading1"], fontName=f"{head}-Bold",
            fontSize=15, leading=26, alignment=TA_RIGHT, textColor=navy, spaceBefore=16, spaceAfter=8,
        ),
        "h2": ParagraphStyle(
            "h2", parent=base["Heading2"], fontName=f"{body}-Bold",
            fontSize=11.5, leading=20, alignment=TA_RIGHT, textColor=ink, spaceBefore=2, spaceAfter=4,
        ),
        "day": ParagraphStyle(
            "day", parent=base["Normal"], fontName=f"{body}-Bold",
            fontSize=9, leading=14, alignment=TA_RIGHT, textColor=gold, spaceBefore=2, spaceAfter=2,
        ),
        "body": ParagraphStyle(
            "body", parent=base["Normal"], fontName=body,
            fontSize=10.5, leading=19, alignment=TA_RIGHT, textColor=ink, spaceAfter=4,
        ),
        "muted": ParagraphStyle(
            "muted", parent=base["Normal"], fontName=body,
            fontSize=9, leading=15, alignment=TA_RIGHT, textColor=muted, spaceAfter=2,
        ),
        "label": ParagraphStyle(
            "label", parent=base["Normal"], fontName=f"{body}-Bold",
            fontSize=9.5, leading=15, alignment=TA_RIGHT, textColor=navy, spaceBefore=5, spaceAfter=1,
        ),
        "footer": ParagraphStyle(
            "footer", parent=base["Normal"], fontName=body,
            fontSize=8, leading=12, alignment=TA_CENTER, textColor=colors.HexColor("#94a3b8"),
        ),
        "table": ParagraphStyle(
            "table", parent=base["Normal"], fontName=body,
            fontSize=8.5, leading=13, alignment=TA_RIGHT, textColor=ink,
        ),
        "table_h": ParagraphStyle(
            "table_h", parent=base["Normal"], fontName=f"{body}-Bold",
            fontSize=8.5, leading=13, alignment=TA_RIGHT, textColor=colors.white,
        ),
    }


def p(styles: dict, key: str, text: str) -> Paragraph:
    return Paragraph(ar(text), styles[key])


def issue_block(styles: dict, issue: dict) -> list:
    rows = [
        p(styles, "day", issue["day"]),
        p(styles, "h2", f"البند {issue['id']}: {issue['title']}  ·  الأولوية: {issue['priority']}"),
        p(styles, "label", "الصفحات المتأثرة"),
        p(styles, "body", issue["pages"]),
        p(styles, "label", "وصف المشكلة"),
        p(styles, "body", issue["problem"]),
        p(styles, "label", "ما تم تنفيذه"),
        p(styles, "body", issue["fix"]),
        p(styles, "label", "النتيجة"),
        p(styles, "body", issue["result"]),
        Spacer(1, 0.12 * cm),
        HRFlowable(width="100%", thickness=0.7, color=colors.HexColor("#e2e8f0"), spaceBefore=2, spaceAfter=8),
    ]
    return [KeepTogether(rows)]


def build_pdf(out_path: Path) -> Path:
    head, body = register_fonts()
    styles = build_styles(head, body)
    out_path.parent.mkdir(parents=True, exist_ok=True)

    doc = SimpleDocTemplate(
        str(out_path),
        pagesize=A4,
        rightMargin=1.5 * cm,
        leftMargin=1.5 * cm,
        topMargin=1.4 * cm,
        bottomMargin=1.5 * cm,
        title=REPORT_META["title"],
        author=REPORT_META["product"],
    )

    story: list = []
    today_ar = datetime.now().strftime("%d / %m / %Y")
    story.append(Spacer(1, 0.8 * cm))
    story.append(p(styles, "cover_title", f"{REPORT_META['product']} — {REPORT_META['title']}"))
    story.append(p(styles, "cover_sub", REPORT_META["subtitle"]))
    story.append(p(styles, "cover_sub", REPORT_META["audience"]))
    story.append(p(styles, "muted", f"الفترة المشمولة: {REPORT_META['period']}"))
    story.append(p(styles, "muted", f"تاريخ إصدار الملف: {today_ar}"))
    story.append(Spacer(1, 0.35 * cm))
    story.append(HRFlowable(width="100%", thickness=1.4, color=colors.HexColor("#1E4E8C"), spaceBefore=4, spaceAfter=12))

    story.append(p(styles, "h1", "١) الملخص التنفيذي"))
    yesterday_n = sum(1 for i in ISSUES if i["day"].startswith("أمس"))
    today_n = sum(1 for i in ISSUES if i["day"].startswith("اليوم"))
    story.append(
        p(
            styles,
            "body",
            f"خلال يومين متتاليين أُغلقت {len(ISSUES)} بنداً مؤثراً على التشغيل والثقة: "
            f"{yesterday_n} أمس و{today_n} اليوم. "
            "شملت الصلاحيات، العملة، اعتماد المعلمين، الدفع، الإحالات، عقود الموظفين، "
            "صفحة الباقات، دليل المستخدمين، أداء الموقع، وتوحيد رسائل التنبيه — "
            "إضافة إلى تنظيف الهوية وإصلاح ملف محركات البحث.",
        )
    )

    story.append(p(styles, "h1", "٢) نظرة سريعة"))
    table_data = [[
        Paragraph(ar("الحالة"), styles["table_h"]),
        Paragraph(ar("الأولوية"), styles["table_h"]),
        Paragraph(ar("العنوان"), styles["table_h"]),
        Paragraph(ar("اليوم"), styles["table_h"]),
        Paragraph(ar("م"), styles["table_h"]),
    ]]
    for issue in ISSUES:
        day_short = "أمس" if issue["day"].startswith("أمس") else "اليوم"
        table_data.append([
            Paragraph(ar("مكتمل"), styles["table"]),
            Paragraph(ar(issue["priority"]), styles["table"]),
            Paragraph(ar(issue["title"]), styles["table"]),
            Paragraph(ar(day_short), styles["table"]),
            Paragraph(ar(issue["id"]), styles["table"]),
        ])

    table = Table(table_data, colWidths=[1.8 * cm, 1.9 * cm, 9.2 * cm, 1.6 * cm, 1.5 * cm])
    table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#1E4E8C")),
                ("BACKGROUND", (0, 1), (-1, -1), colors.HexColor("#f8fafc")),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.HexColor("#f8fafc"), colors.white]),
                ("GRID", (0, 0), (-1, -1), 0.4, colors.HexColor("#cbd5e1")),
                ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                ("ALIGN", (0, 0), (-1, -1), "RIGHT"),
                ("LEFTPADDING", (0, 0), (-1, -1), 4),
                ("RIGHTPADDING", (0, 0), (-1, -1), 4),
                ("TOPPADDING", (0, 0), (-1, -1), 5),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 5),
            ]
        )
    )
    story.append(table)

    story.append(p(styles, "h1", "٣) تفصيل الأعطال والإصلاحات"))
    for issue in ISSUES:
        story.extend(issue_block(styles, issue))

    story.append(p(styles, "h1", "٤) ملاحظات للإدارة"))
    for note in NOTES:
        story.append(p(styles, "body", f"• {note}"))

    story.append(Spacer(1, 0.7 * cm))
    story.append(HRFlowable(width="100%", thickness=0.8, color=colors.HexColor("#e2e8f0")))
    story.append(Spacer(1, 0.25 * cm))
    story.append(p(styles, "footer", f"{REPORT_META['product']} · تقرير إصلاحات 28–29 سبتمبر 2026 · {today_ar}"))
    story.append(p(styles, "footer", "الخط: Amiri للعناوين · Noto Naskh Arabic للنصوص"))

    def page_number(canvas, _doc):
        canvas.saveState()
        canvas.setFillColor(colors.HexColor("#94a3b8"))
        canvas.setFont(body, 8)
        canvas.drawCentredString(A4[0] / 2, 0.95 * cm, ar(f"صفحة {canvas.getPageNumber()}"))
        canvas.restoreState()

    doc.build(story, onFirstPage=page_number, onLaterPages=page_number)
    return out_path


def main() -> None:
    default_out = ROOT / "reports" / f"hesetak-fixes-report-{datetime.now().strftime('%Y%m%d')}.pdf"
    parser = argparse.ArgumentParser(description="تقرير PDF لإصلاحات حصتك")
    parser.add_argument("--out", type=Path, default=default_out)
    args = parser.parse_args()
    path = build_pdf(args.out.resolve())
    print(f"تم إنشاء التقرير: {path}")
    print(f"عدد البنود: {len(ISSUES)}")


if __name__ == "__main__":
    main()

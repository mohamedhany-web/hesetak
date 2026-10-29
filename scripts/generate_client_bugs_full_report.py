#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
تقرير شامل للعميل — كل الأعطال والإصلاحات (حصتك)

يغطي دفعات العمل على المنصة الحية hissatak.online خلال أكتوبر 2026:
  - تجربة الموقع العام (CX)
  - لوحة الإدارة (18 بنداً)
  - دخول الحصص / التوظيف / التسكين

الاستخدام:
  python3 scripts/generate_client_bugs_full_report.py
  python3 scripts/generate_client_bugs_full_report.py --out reports/تقرير-العميل.pdf
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
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)

ROOT = Path(__file__).resolve().parents[1]
FONTS_DIR = ROOT / "resources" / "fonts"

# ألوان هوية حصتك
NAVY = colors.HexColor("#0B1F3A")
TEAL = colors.HexColor("#0F5C56")
GOLD = colors.HexColor("#C9A227")
INK = colors.HexColor("#0f172a")
MUTED = colors.HexColor("#64748b")
SOFT = colors.HexColor("#f1f5f9")
OK_BG = colors.HexColor("#ecfdf5")
OK_FG = colors.HexColor("#047857")
LINE = colors.HexColor("#e2e8f0")

REPORT_META = {
    "title": "تقرير شامل بالأعطال والإصلاحات",
    "product": "منصة حصتك",
    "site": "https://hissatak.online",
    "audience": "تقرير رسمي للعميل — مراجعة وإغلاق البنود",
    "live_head": "012665f",
    "period": "أكتوبر 2026",
}

# ---------------------------------------------------------------------------
# كل البنود التي اشتغلنا عليها في هذه الدورة (دفعات متتالية)
# status: تمت المراجعة والنشر على الإنتاج
# ---------------------------------------------------------------------------
BATCHES: list[dict] = [
    {
        "code": "أ",
        "name": "تجربة الموقع العام (CX) — دفعة أولى",
        "deploy": "88c8559 وما قبلها",
        "summary": "إصلاحات واجهة الزائر والطالب: بحث، مناهج، تسجيل، باقات، نوافذ منبثقة، وتجارب الحجز.",
        "issues": [
            {
                "id": "أ-1",
                "title": "نافذة الإعلان المنبثق تظهر تحت المحتوى أو لا تغطي الشاشة",
                "url": "الصفحة الرئيسية / الصفحات العامة",
                "problem": "الإعلان كان يختفي خلف عناصر الصفحة أو يظهر بشكل غير صحيح على الجوال وسطح المكتب.",
                "fix": "تثبيت الطبقة فوق الصفحة بالكامل وربطها بجسم الصفحة مع ترتيب عرض صحيح.",
                "priority": "مرتفع",
            },
            {
                "id": "أ-2",
                "title": "باقة حصتين وباقة ٨ حصص لا تصل لنفس مسار الدفع",
                "url": "/pricing · صفحة الباقات",
                "problem": "اختيار ٢ حصص كان يفتح مساراً مختلفاً عن ٨ حصص فيربك الطالب عند الدفع.",
                "fix": "توحيد مسار الدفع لباقات الحصص الخاصة بنفس آلية الجلسات والمتغيرات.",
                "priority": "حرج",
            },
            {
                "id": "أ-3",
                "title": "قسم باقات الحصص ١:١ فارغ في إدارة الباقات",
                "url": "/admin/packages",
                "problem": "تبويب باقات الحصص الخاصة لم يظهر المحتوى المتوقع للإدارة.",
                "fix": "استعادة عرض باقات الحصص ١:١ ضمن لوحة الباقات مع التبويب الصحيح.",
                "priority": "مرتفع",
            },
            {
                "id": "أ-4",
                "title": "قواعد تسعير الباقات — حقول غير واضحة أو ناقصة",
                "url": "إدارة قواعد تسعير باقات الخدمات",
                "problem": "نموذج القواعد لم يسهّل إدخال الأسعار والخيارات بشكل صحيح من أول شاشة.",
                "fix": "ترتيب حقول النموذج وإبراز الحقول الأساسية أولاً.",
                "priority": "متوسط",
            },
            {
                "id": "أ-5",
                "title": "خطأ عند أول فتح لمجلدات الفيديو في المكتبة",
                "url": "مكتبة الفيديوهات / المجلدات",
                "problem": "تحميل الصفحة الأولى قد يفشل بسبب عدّ عناصر فرعية غير آمن.",
                "fix": "تحصين العدّ مع معالجة الأخطاء حتى لا تسقط الصفحة.",
                "priority": "مرتفع",
            },
            {
                "id": "أ-6",
                "title": "تبويبات المناهج في الرئيسية بدل ترند المعلمين",
                "url": "الصفحة الرئيسية",
                "problem": "المطلوب مستعرض مناهج بمراحل/مسارات بدل قائمة معلمين فقط.",
                "fix": "إضافة مستعرض المناهج مع تفويض النقر للمراحل بعد تحميل البطاقات.",
                "priority": "مرتفع",
            },
            {
                "id": "أ-7",
                "title": "مشاكل عامة سابقة في الحجز والبحث والتسجيل (دفعة ١٧ بنداً)",
                "url": "صفحات الطلب · التسجيل · المناهج · الدروس",
                "problem": "مجموعة ملاحظات عميل سابقة: بحث، مناهج، تسجيل مواد، روابط، وتجربة الحجز.",
                "fix": "إغلاق البنود المؤثرة على الإنتاج ومراجعتها على الموقع الحي.",
                "priority": "مرتفع",
            },
        ],
    },
    {
        "code": "ب",
        "name": "لوحة الإدارة — ١٨ بنداً",
        "deploy": "9bca9cc",
        "summary": "إصلاح أخطاء صفحات الإدارة: روابط مكسورة، صفحات بيضاء، أزرار إضافة/حفظ، وتسميات خاطئة.",
        "issues": [
            {
                "id": "ب-1",
                "title": "مساعد التشغيل يعطي خطأ",
                "url": "/admin/ops-guide",
                "problem": "الصفحة تنهار بسبب رابط داخلي يحتاج معامل جمهور البريد.",
                "fix": "توليد الروابط بمعاملات افتراضية آمنة مع تجاهل الروابط المعطوبة.",
                "priority": "حرج",
            },
            {
                "id": "ب-2",
                "title": "ظهور معلم تجريبي باسم Test في إجراءات المعلمين",
                "url": "المعلمون المفعّلون · قوائم اختيار المعلمين",
                "problem": "حساب تجريبي يظهر في القوائم والإجراءات فيربك التشغيل.",
                "fix": "استبعاد الأسماء التجريبية (Test / اختبار) من قوائم إجراءات المعلمين.",
                "priority": "متوسط",
            },
            {
                "id": "ب-3",
                "title": "فتح ملف معلم / صفحة عامة تعطي خطأ",
                "url": "/admin/users/{uuid}",
                "problem": "رابط الصفحة العامة يُفتح قبل الاعتماد فيظهر خطأ للزائر أو للإدارة.",
                "fix": "إظهار رابط الصفحة العامة فقط بعد الاعتماد والنشر؛ وإلا توجيه لملف المطابقة.",
                "priority": "مرتفع",
            },
            {
                "id": "ب-4",
                "title": "الأسئلة الشائعة من لوحة التحكم تظهر منفصلة عن الحالية",
                "url": "/faq · /admin/faq",
                "problem": "أسئلة الإدارة كانت في قسم منفصل عن محتوى الصفحة العامة.",
                "fix": "جعل أسئلة قاعدة البيانات المحتوى الأساسي عند وجودها.",
                "priority": "متوسط",
            },
            {
                "id": "ب-5",
                "title": "المصادقة الثنائية تظهر غير مفعّلة",
                "url": "/admin/system-settings",
                "problem": "التفعيل يتطلب رمز بريد ثم تأكيد — يبدو «غير مفعّل» حتى اكتمال الخطوتين.",
                "fix": "الإبقاء على مسار التفعيل الآمن مع توضيح الواجهة؛ التفعيل يتم بعد تأكيد الرمز.",
                "priority": "متوسط",
            },
            {
                "id": "ب-6",
                "title": "أرقام «طلبات جديدة يومياً» بلا تفسير واضح",
                "url": "/admin/sales",
                "problem": "الرسم اليومي والأرقام المعلّقة لم تكن موضّحة بما يكفي للإدارة.",
                "fix": "توضيح التسميات وربط المؤشرات بالفترة المحددة.",
                "priority": "منخفض",
            },
            {
                "id": "ب-7",
                "title": "صفحة عمولات CRM بيضاء / بلا إضافة",
                "url": "/admin/crm/commissions",
                "problem": "لا توجد واجهة إضافة واضحة للعمولات اليدوية.",
                "fix": "صفحة قائمة سليمة + نموذج إضافة عمولة مرتبطة بـ Lead.",
                "priority": "مرتفع",
            },
            {
                "id": "ب-8",
                "title": "إدارة الإجازات وطلبات المدربين بلا إضافة",
                "url": "/admin/leaves · /admin/instructor-requests",
                "problem": "الصفحات للمراجعة فقط بدون إنشاء من الإدارة.",
                "fix": "إضافة مسارات ونماذج إنشاء لإجازات الموظفين وطلبات المدربين.",
                "priority": "مرتفع",
            },
            {
                "id": "ب-9",
                "title": "رقابة المعلمين كانت تسمية خاطئة + ترتيب عشوائي للوقت",
                "url": "/admin/quality-control/students",
                "problem": "القائمة تحمل تفاصيل الطلاب لكن التسمية/العمود يقول معلم؛ والترتيب غير منطقي.",
                "fix": "تسمية «رقابة الطالب» وترتيب حسب آخر ظهور + تصحيح عمود الطالب.",
                "priority": "مرتفع",
            },
            {
                "id": "ب-10",
                "title": "لا يمكن تعديل قوالب الرسائل",
                "url": "/admin/messages/templates",
                "problem": "زر التعديل كان شكلياً بدون حفظ.",
                "fix": "تفعيل تعديل القالب عبر نافذة وحفظ فعلي.",
                "priority": "مرتفع",
            },
            {
                "id": "ب-11",
                "title": "أولياء الأمور — لا يمكن إضافة محتوى",
                "url": "/admin/parents",
                "problem": "القائمة للعرض والربط فقط بدون إنشاء ولي أمر.",
                "fix": "إضافة إنشاء ولي أمر وربط بالطلاب.",
                "priority": "مرتفع",
            },
            {
                "id": "ب-12",
                "title": "زر كورس جديد لا يعمل",
                "url": "/admin/courses/create",
                "problem": "المسار القديم بلا قوالب فيعطي خطأ.",
                "fix": "توجيه إنشاء الكورس إلى مسار الكورسات المتقدمة الفعلي.",
                "priority": "حرج",
            },
            {
                "id": "ب-13",
                "title": "إحصائيات الإشعارات تعطي خطأ",
                "url": "/admin/notifications/statistics",
                "problem": "الصفحة مفقودة من القوالب.",
                "fix": "إنشاء صفحة الإحصائيات وعرض المؤشرات.",
                "priority": "مرتفع",
            },
            {
                "id": "ب-14",
                "title": "حفظ اتفاقية جديدة يعطي خطأ",
                "url": "/admin/agreements",
                "problem": "نوع «استشارات» غير متوافق مع عمود قاعدة البيانات.",
                "fix": "تخزين النوع المسموح مع تمييز الاستشارات عبر نوع الفوترة.",
                "priority": "حرج",
            },
            {
                "id": "ب-15",
                "title": "وقت جلسات البث يظهر خاطئاً",
                "url": "/admin/live-sessions",
                "problem": "العرض كان بتوقيت التخزين وليس توقيت الأكاديمية/المستخدم.",
                "fix": "عرض المواعيد بتوقيت المنصة/المستخدم الصحيح.",
                "priority": "متوسط",
            },
            {
                "id": "ب-16",
                "title": "تسجيلات الشاشة — لا يمكن إضافة فيديو",
                "url": "/admin/live-recordings/create",
                "problem": "النموذج يقبل مساراً نصياً فقط بدون رفع ملف.",
                "fix": "دعم رفع ملف فيديو مع المسار/الرابط الخارجي.",
                "priority": "مرتفع",
            },
            {
                "id": "ب-17",
                "title": "تقارير n8n بلا تحكم",
                "url": "/admin/n8n/live-session-reports",
                "problem": "عرض فقط بدون إعادة جدولة أو تغيير الحالة.",
                "fix": "أزرار: إعادة جدولة / مكتمل / فاشل.",
                "priority": "متوسط",
            },
            {
                "id": "ب-18",
                "title": "التسويق الشخصي — لا يمكن النشر",
                "url": "/admin/personal-branding/.../edit",
                "problem": "الحفظ لا ينشر؛ وأسباب الرفض غير ظاهرة في التعديل.",
                "fix": "عرض نواقص النشر في صفحة التعديل + زر موافقة عند الجاهزية.",
                "priority": "مرتفع",
            },
        ],
    },
    {
        "code": "ج",
        "name": "دخول الحصص · التوظيف · التسكين",
        "deploy": "012665f",
        "summary": "إغلاق بلاغات دخول الطالب للحصة، قيود الجهاز، نموذج المعلم، وملاحظات التوظيف والتسكين والحضور.",
        "issues": [
            {
                "id": "ج-1",
                "title": "الطالب لا يدخل الحصة بسهولة (تكرار مشكلة سابقة)",
                "url": "حساب الطالب · الحصة الخاصة",
                "problem": "بعد تحديث الزر، الدخول يفشل أو يرجع لنفس الصفحة لأن الغرفة أُغلقت مبكراً بينما الحصة ما زالت مجدولة.",
                "fix": "إعادة فتح الغرفة تلقائياً عند الدخول طالما الحصة مجدولة وغير منتهية الصلاحية.",
                "priority": "حرج",
            },
            {
                "id": "ج-2",
                "title": "زر «ادخل الآن» لا ينقل لأي وجهة",
                "url": "/one-to-one-sessions/{id}",
                "problem": "الضغط يبدو بلا نتيجة (رجوع صامت لنفس الصفحة برسالة انتهاء الاجتماع).",
                "fix": "ربط الزر بمسار الغرفة المباشر مع منطق إعادة الفتح أعلاه.",
                "priority": "حرج",
            },
            {
                "id": "ج-3",
                "title": "منع دخول حساب الطالب من أكثر من جهاز",
                "url": "جلسة الطالب على المنصة",
                "problem": "الحاجة لجلسة واحدة نشطة فقط لكل طالب.",
                "fix": "تشديد سياسة الجهاز الواحد وتجديد القفل عند دخول الغرفة؛ الجهاز الآخر يُخرج تلقائياً.",
                "priority": "حرج",
            },
            {
                "id": "ج-4",
                "title": "ميزة الحفظ التلقائي أثناء تعبئة بيانات المعلم",
                "url": "/tutor/apply/profile",
                "problem": "فقدان البيانات عند انقطاع أو إغلاق الصفحة أثناء التعبئة الطويلة.",
                "fix": "حفظ مسودة محلياً تلقائياً أثناء الكتابة واستعادتها عند العودة.",
                "priority": "متوسط",
            },
            {
                "id": "ج-5",
                "title": "منع Screenshots من داخل الحصة",
                "url": "غرفة الحصة المباشرة",
                "problem": "طلب حماية من التصوير داخل الجلسة.",
                "fix": "حماية خفيفة داخل الغرفة (منع التحديد واختصارات شائعة). ملاحظة: تصوير نظام التشغيل لا يُمنع بالكامل من المتصفح.",
                "priority": "متوسط",
            },
            {
                "id": "ج-6",
                "title": "تحميل طويل في ملء بيانات المعلم",
                "url": "/tutor/apply/profile",
                "problem": "الصفحة ثقيلة عند الفتح فتبدو معطّلة.",
                "fix": "تخفيف تحميل السكربتات وتحسين ترتيب التحميل مع الحفظ التلقائي.",
                "priority": "متوسط",
            },
            {
                "id": "ج-7",
                "title": "هاتف يقبل أقل من ١١ رقم · كلمة مرور ضعيفة",
                "url": "تسجيل / بيانات المعلم",
                "problem": "قبول أرقام قصيرة وكلمات مرور بلا أرقام أو قصيرة جداً.",
                "fix": "كلمة مرور ≥ ١٠ أحرف مع حرف ورقم؛ رفض الأرقام المصرية القصيرة عن المطلوب.",
                "priority": "مرتفع",
            },
            {
                "id": "ج-8",
                "title": "مواعيد المقابلات — ترتيب الجدول مختل",
                "url": "/admin/tutor-interview-slots",
                "problem": "الماضي والمستقبل مختلطان فيبدو الجدول غير منظم.",
                "fix": "ترتيب زمني موحّد حسب وقت الموعد.",
                "priority": "متوسط",
            },
            {
                "id": "ج-9",
                "title": "زر الملف العام يعطي Error من مراجعة طلب التوظيف",
                "url": "/admin/tutor-applications/{id}",
                "problem": "فتح الصفحة العامة قبل اكتمال الاعتماد/النشر.",
                "fix": "إظهار الرابط العام فقط للحسابات المنشورة؛ وإلا ملف المطابقة الداخلي.",
                "priority": "مرتفع",
            },
            {
                "id": "ج-10",
                "title": "تفعيل طلبات بانتظار التفعيل: «٣ مهارات» والمهارات لا تظهر",
                "url": "/admin/tutor-applications/...",
                "problem": "الإدارة لا ترى المهارات المحتسبة بينما التفعيل يرفض بالنواقص.",
                "fix": "عرض نفس قائمة المهارات المستخدمة في التفعيل (بما فيها أسماء المواد).",
                "priority": "حرج",
            },
            {
                "id": "ج-11",
                "title": "لوحة التوظيف: ١٣ معلماً نشيطاً بينما المفعّلون ٢",
                "url": "/admin/tutor-applications",
                "problem": "الرقم كان يعدّ كل حسابات المعلمين وليس طلبات التفعيل الفعلية.",
                "fix": "توحيد العدّ مع قائمة المفعّلين الفعلية (بدون حسابات تجريبية).",
                "priority": "مرتفع",
            },
            {
                "id": "ج-12",
                "title": "إضافة طالب / وظائف إدارية — أخطاء بعد الحفظ الفاشل",
                "url": "/admin/users/create?from=students · وظائف إدارية",
                "problem": "بعد فشل التحقق تنكسر الصفحة؛ وتحقق الرواتب قد يرفض بغير سبب واضح.",
                "fix": "إرجاع البيانات اللازمة للنموذج بعد الخطأ وتصحيح تحقق الرواتب/الكود.",
                "priority": "مرتفع",
            },
            {
                "id": "ج-13",
                "title": "التسكين: تعديل تاريخ حصة واحدة يغيّر كل التواريخ",
                "url": "/admin/placement",
                "problem": "بعد خطأ أو إعادة تحميل يظهر نفس الموعد على كل الصفوف.",
                "fix": "تثبيت رقم الحصة المعدَّلة عند الحفظ والخطأ حتى لا تتأثر بقية الصفوف.",
                "priority": "حرج",
            },
            {
                "id": "ج-14",
                "title": "أرصدة الحصص: البحث يبدو باسم المعلم رغم placeholder الطالب",
                "url": "/admin/student-entitlements",
                "problem": "البحث غير واضح عند وجود أسماء باقات/مجموعات بجانب اسم الطالب.",
                "fix": "توسيع البحث لطالب + باقة + مجموعة مع تحديث نص البحث.",
                "priority": "متوسط",
            },
            {
                "id": "ج-15",
                "title": "الحضور: الجلسات المكتملة/الفائتة لا تظهر عند البحث",
                "url": "/admin/attendance?status=late",
                "problem": "الفلاتر لا تُظهر المحاضرات السابقة بالترتيب المتوقع.",
                "fix": "ترتيب حسب موعد المحاضرة وافتراض المحاضرات السابقة مع فلتر الحالة.",
                "priority": "متوسط",
            },
            {
                "id": "ج-16",
                "title": "التسكين الخاص: باقة واحدة فقط دون إضافة باقات أخرى في نفس الشاشة",
                "url": "/admin/placement/create?mode=private",
                "problem": "التوقع إضافة عدة باقات دفعة واحدة أثناء التسكين.",
                "fix": "توضيح أن كل تسكين لباقة/رصيد واحد؛ باقات إضافية عبر منح رصيد ثم تسكين جديد مع رابط مباشر.",
                "priority": "منخفض",
            },
        ],
    },
]


NOTES = [
    "جميع البنود أعلاه نُشرت على الإنتاج https://hissatak.online وتمت مراجعتها من جهة التطوير.",
    "رقم الإصدار الحي الأخير لهذه الدورة: 012665f (بعد 9bca9cc و 0f901a8).",
    "منع تصوير الشاشة من نظام التشغيل نفسه غير ممكن بشكل مطلق من داخل المتصفح؛ طُبّقت حماية معقولة داخل الغرفة.",
    "المصادقة الثنائية تُفعَّل بخطوتين (إرسال رمز البريد ثم التأكيد) لضمان أمان حسابات الإدارة.",
    "يُفضَّل مرور تشغيل سريع من العميل على: دخول حصة طالب، تفعيل معلم، تسكين موعد واحد، ومساعد التشغيل.",
]


def ar(text: str) -> str:
    if not text:
        return ""
    eastern = "٠١٢٣٤٥٦٧٨٩"
    western = "0123456789"
    text = str(text).translate(str.maketrans(eastern, western))
    for bad, good in {
        "\u2014": "-",
        "\u2013": "-",
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
    amiri_r = FONTS_DIR / "Amiri-Regular.ttf"
    amiri_b = FONTS_DIR / "Amiri-Bold.ttf"
    noto_r = FONTS_DIR / "NotoNaskhArabic-Regular.ttf"
    noto_b = FONTS_DIR / "NotoNaskhArabic-Bold.ttf"
    system = Path("/Library/Fonts/Arial Unicode.ttf")

    body, head = "HesetakBody", "HesetakHead"
    if not noto_r.exists() and not amiri_r.exists() and not system.exists():
        raise FileNotFoundError(f"لا يوجد خط عربي في {FONTS_DIR}")

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
    return {
        "cover_brand": ParagraphStyle(
            "cover_brand", parent=base["Normal"], fontName=f"{head}-Bold",
            fontSize=14, leading=22, alignment=TA_CENTER, textColor=GOLD, spaceAfter=6,
        ),
        "cover_title": ParagraphStyle(
            "cover_title", parent=base["Title"], fontName=f"{head}-Bold",
            fontSize=22, leading=34, alignment=TA_CENTER, textColor=NAVY, spaceAfter=10,
        ),
        "cover_sub": ParagraphStyle(
            "cover_sub", parent=base["Normal"], fontName=body,
            fontSize=11.5, leading=20, alignment=TA_CENTER, textColor=colors.HexColor("#334155"), spaceAfter=5,
        ),
        "h1": ParagraphStyle(
            "h1", parent=base["Heading1"], fontName=f"{head}-Bold",
            fontSize=14.5, leading=24, alignment=TA_RIGHT, textColor=NAVY, spaceBefore=14, spaceAfter=8,
        ),
        "h2": ParagraphStyle(
            "h2", parent=base["Heading2"], fontName=f"{body}-Bold",
            fontSize=11, leading=18, alignment=TA_RIGHT, textColor=INK, spaceBefore=2, spaceAfter=3,
        ),
        "batch": ParagraphStyle(
            "batch", parent=base["Normal"], fontName=f"{body}-Bold",
            fontSize=9.5, leading=15, alignment=TA_RIGHT, textColor=TEAL, spaceBefore=2, spaceAfter=2,
        ),
        "body": ParagraphStyle(
            "body", parent=base["Normal"], fontName=body,
            fontSize=10, leading=17.5, alignment=TA_RIGHT, textColor=INK, spaceAfter=3,
        ),
        "muted": ParagraphStyle(
            "muted", parent=base["Normal"], fontName=body,
            fontSize=8.5, leading=14, alignment=TA_RIGHT, textColor=MUTED, spaceAfter=2,
        ),
        "label": ParagraphStyle(
            "label", parent=base["Normal"], fontName=f"{body}-Bold",
            fontSize=9, leading=14, alignment=TA_RIGHT, textColor=TEAL, spaceBefore=4, spaceAfter=1,
        ),
        "status": ParagraphStyle(
            "status", parent=base["Normal"], fontName=f"{body}-Bold",
            fontSize=9, leading=14, alignment=TA_RIGHT, textColor=OK_FG, spaceBefore=2, spaceAfter=2,
        ),
        "footer": ParagraphStyle(
            "footer", parent=base["Normal"], fontName=body,
            fontSize=7.5, leading=11, alignment=TA_CENTER, textColor=colors.HexColor("#94a3b8"),
        ),
        "table": ParagraphStyle(
            "table", parent=base["Normal"], fontName=body,
            fontSize=7.8, leading=12, alignment=TA_RIGHT, textColor=INK,
        ),
        "table_h": ParagraphStyle(
            "table_h", parent=base["Normal"], fontName=f"{body}-Bold",
            fontSize=8, leading=12, alignment=TA_RIGHT, textColor=colors.white,
        ),
        "kpi": ParagraphStyle(
            "kpi", parent=base["Normal"], fontName=f"{body}-Bold",
            fontSize=16, leading=22, alignment=TA_CENTER, textColor=NAVY,
        ),
        "kpi_l": ParagraphStyle(
            "kpi_l", parent=base["Normal"], fontName=body,
            fontSize=8, leading=12, alignment=TA_CENTER, textColor=MUTED,
        ),
    }


def p(styles: dict, key: str, text: str) -> Paragraph:
    return Paragraph(ar(text), styles[key])


def all_issues() -> list[dict]:
    rows = []
    for batch in BATCHES:
        for issue in batch["issues"]:
            rows.append({**issue, "batch": batch["name"], "batch_code": batch["code"], "deploy": batch["deploy"]})
    return rows


def kpi_table(styles: dict) -> Table:
    total = sum(len(b["issues"]) for b in BATCHES)
    critical = sum(1 for i in all_issues() if i["priority"] == "حرج")
    high = sum(1 for i in all_issues() if i["priority"] == "مرتفع")
    data = [
        [
            Paragraph(ar(str(len(BATCHES))), styles["kpi"]),
            Paragraph(ar(str(total)), styles["kpi"]),
            Paragraph(ar(str(critical)), styles["kpi"]),
            Paragraph(ar(str(high)), styles["kpi"]),
            Paragraph(ar("١٠٠٪"), styles["kpi"]),
        ],
        [
            Paragraph(ar("دفعات عمل"), styles["kpi_l"]),
            Paragraph(ar("إجمالي البنود"), styles["kpi_l"]),
            Paragraph(ar("حرج"), styles["kpi_l"]),
            Paragraph(ar("مرتفع"), styles["kpi_l"]),
            Paragraph(ar("مراجع ومنشور"), styles["kpi_l"]),
        ],
    ]
    t = Table(data, colWidths=[3.2 * cm, 3.2 * cm, 3.2 * cm, 3.2 * cm, 3.2 * cm])
    t.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), SOFT),
                ("BOX", (0, 0), (-1, -1), 1, TEAL),
                ("INNERGRID", (0, 0), (-1, -1), 0.4, LINE),
                ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                ("TOPPADDING", (0, 0), (-1, -1), 8),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 8),
            ]
        )
    )
    return t


def overview_table(styles: dict) -> Table:
    header = [
        Paragraph(ar("المراجعة"), styles["table_h"]),
        Paragraph(ar("الأولوية"), styles["table_h"]),
        Paragraph(ar("العنوان"), styles["table_h"]),
        Paragraph(ar("الدفعة"), styles["table_h"]),
        Paragraph(ar("م"), styles["table_h"]),
    ]
    data = [header]
    for issue in all_issues():
        data.append(
            [
                Paragraph(ar("تمت ✓"), styles["table"]),
                Paragraph(ar(issue["priority"]), styles["table"]),
                Paragraph(ar(issue["title"]), styles["table"]),
                Paragraph(ar(issue["batch_code"]), styles["table"]),
                Paragraph(ar(issue["id"]), styles["table"]),
            ]
        )
    t = Table(data, colWidths=[2.0 * cm, 1.7 * cm, 9.0 * cm, 1.4 * cm, 1.9 * cm])
    t.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), NAVY),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [SOFT, colors.white]),
                ("GRID", (0, 0), (-1, -1), 0.35, LINE),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("ALIGN", (0, 0), (-1, -1), "RIGHT"),
                ("LEFTPADDING", (0, 0), (-1, -1), 3),
                ("RIGHTPADDING", (0, 0), (-1, -1), 3),
                ("TOPPADDING", (0, 0), (-1, -1), 4),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 4),
            ]
        )
    )
    return t


def issue_block(styles: dict, issue: dict, batch_name: str) -> list:
    rows = [
        p(styles, "batch", f"الدفعة: {batch_name}"),
        p(styles, "h2", f"{issue['id']} — {issue['title']}"),
        p(styles, "status", f"الحالة: تم الإصلاح · تمت المراجعة · منشور على الإنتاج · الأولوية: {issue['priority']}"),
        p(styles, "label", "الرابط / الصفحة"),
        p(styles, "muted", issue["url"]),
        p(styles, "label", "وصف العطل"),
        p(styles, "body", issue["problem"]),
        p(styles, "label", "الإجراء المنفَّذ"),
        p(styles, "body", issue["fix"]),
        Spacer(1, 0.08 * cm),
        HRFlowable(width="100%", thickness=0.6, color=LINE, spaceBefore=2, spaceAfter=7),
    ]
    return [KeepTogether(rows)]


def build_pdf(out_path: Path) -> Path:
    head, body = register_fonts()
    styles = build_styles(head, body)
    out_path.parent.mkdir(parents=True, exist_ok=True)

    doc = SimpleDocTemplate(
        str(out_path),
        pagesize=A4,
        rightMargin=1.45 * cm,
        leftMargin=1.45 * cm,
        topMargin=1.3 * cm,
        bottomMargin=1.55 * cm,
        title=f"{REPORT_META['product']} — {REPORT_META['title']}",
        author=REPORT_META["product"],
    )

    story: list = []
    today_ar = datetime.now().strftime("%d / %m / %Y — %H:%M")
    total = sum(len(b["issues"]) for b in BATCHES)

    # —— غلاف ——
    story.append(Spacer(1, 1.2 * cm))
    story.append(p(styles, "cover_brand", REPORT_META["product"]))
    story.append(p(styles, "cover_title", REPORT_META["title"]))
    story.append(p(styles, "cover_sub", REPORT_META["audience"]))
    story.append(p(styles, "cover_sub", f"الموقع الحي: {REPORT_META['site']}"))
    story.append(p(styles, "muted", f"الفترة: {REPORT_META['period']}"))
    story.append(p(styles, "muted", f"إصدار الإنتاج الحالي: {REPORT_META['live_head']}"))
    story.append(p(styles, "muted", f"تاريخ إصدار الملف: {today_ar}"))
    story.append(Spacer(1, 0.4 * cm))
    story.append(HRFlowable(width="100%", thickness=2, color=TEAL, spaceBefore=4, spaceAfter=14))

    story.append(p(styles, "h1", "١) الملخص التنفيذي"))
    story.append(
        p(
            styles,
            "body",
            f"يوثّق هذا التقرير {total} بنداً تشغيلياً أُغلقت عبر {len(BATCHES)} دفعات متتالية على منصة حصتك. "
            "شملت تجربة الموقع العام، لوحة الإدارة، دخول الحصص المباشرة، التوظيف، والتسكين. "
            "كل بند أدناه يحمل حالة: تم الإصلاح، تمت المراجعة، ومنشور على الإنتاج.",
        )
    )
    story.append(Spacer(1, 0.25 * cm))
    story.append(kpi_table(styles))
    story.append(Spacer(1, 0.35 * cm))

    story.append(p(styles, "h1", "٢) نظرة سريعة على كل البنود"))
    story.append(overview_table(styles))

    story.append(PageBreak())
    story.append(p(styles, "h1", "٣) تفصيل الأعطال والإصلاحات حسب الدفعة"))

    for batch in BATCHES:
        story.append(Spacer(1, 0.15 * cm))
        story.append(p(styles, "h1", f"دفعة {batch['code']}: {batch['name']}"))
        story.append(p(styles, "muted", f"إصدار النشر: {batch['deploy']}"))
        story.append(p(styles, "body", batch["summary"]))
        story.append(HRFlowable(width="100%", thickness=1.1, color=GOLD, spaceBefore=4, spaceAfter=8))
        for issue in batch["issues"]:
            story.extend(issue_block(styles, issue, batch["name"]))

    story.append(PageBreak())
    story.append(p(styles, "h1", "٤) شهادة المراجعة والنشر"))
    story.append(
        p(
            styles,
            "body",
            "نؤكّد أنه تمت مراجعة البنود المدرجة في هذا التقرير من جهة التطوير، "
            "ونُشرت على الخادم الحي لمنصة حصتك، وأُعيد التحقق من المسارات الحرجة "
            "(دخول الحصة، مساعد التشغيل، التوظيف، إنشاء الكورس، الإحصائيات).",
        )
    )
    cert_data = [
        [
            Paragraph(ar("النتيجة"), styles["table_h"]),
            Paragraph(ar("البند"), styles["table_h"]),
        ],
        [Paragraph(ar("مكتمل"), styles["table"]), Paragraph(ar("إصلاح الكود"), styles["table"])],
        [Paragraph(ar("مكتمل"), styles["table"]), Paragraph(ar("مراجعة داخلية"), styles["table"])],
        [Paragraph(ar("مكتمل"), styles["table"]), Paragraph(ar("نشر على الإنتاج"), styles["table"])],
        [Paragraph(ar("مكتمل"), styles["table"]), Paragraph(ar("تحقق دخان على المسارات الحرجة"), styles["table"])],
    ]
    cert = Table(cert_data, colWidths=[4 * cm, 12 * cm])
    cert.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), TEAL),
                ("BACKGROUND", (0, 1), (0, -1), OK_BG),
                ("ROWBACKGROUNDS", (1, 1), (-1, -1), [colors.white, SOFT]),
                ("GRID", (0, 0), (-1, -1), 0.4, LINE),
                ("VALIGN", (0, 0), (-1, -1), "MIDDLE"),
                ("TOPPADDING", (0, 0), (-1, -1), 6),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
                ("RIGHTPADDING", (0, 0), (-1, -1), 6),
            ]
        )
    )
    story.append(Spacer(1, 0.2 * cm))
    story.append(cert)

    story.append(p(styles, "h1", "٥) ملاحظات للعميل"))
    for note in NOTES:
        story.append(p(styles, "body", f"• {note}"))

    story.append(Spacer(1, 0.8 * cm))
    story.append(HRFlowable(width="100%", thickness=0.8, color=LINE))
    story.append(Spacer(1, 0.2 * cm))
    story.append(
        p(
            styles,
            "footer",
            f"{REPORT_META['product']} · {REPORT_META['site']} · إصدار {REPORT_META['live_head']} · {today_ar}",
        )
    )
    story.append(p(styles, "footer", "وثيقة رسمية للعميل — جميع البنود: تم الإصلاح · تمت المراجعة · منشور"))

    def footer(canvas, _doc):
        canvas.saveState()
        canvas.setFillColor(MUTED)
        canvas.setFont(body, 8)
        canvas.drawCentredString(A4[0] / 2, 0.9 * cm, ar(f"صفحة {canvas.getPageNumber()}"))
        canvas.setStrokeColor(TEAL)
        canvas.setLineWidth(1.2)
        canvas.line(1.45 * cm, A4[1] - 0.7 * cm, A4[0] - 1.45 * cm, A4[1] - 0.7 * cm)
        canvas.restoreState()

    doc.build(story, onFirstPage=footer, onLaterPages=footer)
    return out_path


def main() -> None:
    stamp = datetime.now().strftime("%Y%m%d")
    default_out = ROOT / "reports" / f"hesetak-client-bugs-full-report-{stamp}.pdf"
    parser = argparse.ArgumentParser(description="تقرير PDF شامل بأعطال وإصلاحات حصتك للعميل")
    parser.add_argument("--out", type=Path, default=default_out)
    args = parser.parse_args()
    path = build_pdf(args.out.resolve())
    total = sum(len(b["issues"]) for b in BATCHES)
    print(f"تم إنشاء التقرير: {path}")
    print(f"عدد الدفعات: {len(BATCHES)}")
    print(f"عدد البنود: {total}")


if __name__ == "__main__":
    main()

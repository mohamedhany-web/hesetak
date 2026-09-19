<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\InstructorAgreement;
use App\Models\AgreementPayment;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgreementController extends Controller
{
    public function index()
    {
        $instructor = auth()->user();
        
        $agreements = InstructorAgreement::where('instructor_id', $instructor->id)
            ->with(['payments.course', 'payments.lecture'])
            ->orderBy('created_at', 'desc')
            ->get();

        $activeAgreement = $agreements->where('status', InstructorAgreement::STATUS_ACTIVE)->first();
        
        $stats = [
            'total_earned' => AgreementPayment::where('instructor_id', $instructor->id)
                ->where('status', AgreementPayment::STATUS_PAID)
                ->sum('amount'),
            'pending_amount' => AgreementPayment::where('instructor_id', $instructor->id)
                ->where('status', AgreementPayment::STATUS_APPROVED)
                ->sum('amount'),
            'total_payments' => AgreementPayment::where('instructor_id', $instructor->id)->count(),
        ];

        return view('instructor.agreements.index', compact('agreements', 'activeAgreement', 'stats'));
    }

    public function show(InstructorAgreement $agreement)
    {
        if ($agreement->instructor_id !== auth()->id()) {
            abort(403);
        }

        $agreement->load(['payments.course', 'payments.lecture', 'payments.enrollment.student', 'advancedCourse', 'instructor']);
        
        $stats = [
            'total_earned' => $agreement->paidPayments()->sum('amount'),
            'pending_amount' => $agreement->approvedPayments()->sum('amount'),
            'total_payments' => $agreement->payments()->count(),
            'paid_payments' => $agreement->paidPayments()->count(),
        ];

        return view('instructor.agreements.show', compact('agreement', 'stats'));
    }

    /**
     * تصدير تفعيلات الطلاب (نسبة من الكورس) إلى ملف Excel.
     */
    public function exportActivations(InstructorAgreement $agreement): StreamedResponse
    {
        if ($agreement->instructor_id !== auth()->id()) {
            abort(403);
        }

        if (($agreement->billing_type ?? '') !== 'course_percentage') {
            abort(404, 'هذه الاتفاقية ليست من نوع نسبة من الكورس.');
        }

        $agreement->load(['payments' => fn ($q) => $q->where('type', 'course_activation')->orderBy('created_at')]);
        $agreement->load(['payments.enrollment.student', 'advancedCourse']);

        $activationPayments = $agreement->payments->where('type', 'course_activation');
        $percentage = (float) ($agreement->course_percentage ?? 0);
        $totalEarned = $activationPayments->sum('amount');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('حصتك')
            ->setTitle('تفعيلات الطلاب - اتفاقية ' . ($agreement->agreement_number ?? $agreement->id))
            ->setSubject('نسبة من الكورس');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('تفعيلات الطلاب');
        $sheet->setRightToLeft(true);

        $headerFill = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1E40AF']],
            'font' => ['bold' => true, 'size' => 11, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ];
        $border = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]]];
        $titleFont = ['font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '0F172A']]];
        $summaryFill = [
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
            'font' => ['bold' => true, 'size' => 12],
        ];

        $row = 1;
        $sheet->setCellValue('A' . $row, 'تفعيلات الطلاب وحصتي من كل شراء');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->applyFromArray($titleFont);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $sheet->setCellValue('A' . $row, 'الاتفاقية: ' . ($agreement->agreement_number ?? '—') . ' | الكورس: ' . ($agreement->advancedCourse->title ?? '—'));
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setSize(10)->getColor()->setRGB('475569');
        $row++;

        $sheet->setCellValue('A' . $row, 'تاريخ التصدير: ' . now()->format('Y-m-d H:i'));
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setSize(9)->getColor()->setRGB('64748B');
        $row += 2;

        $headers = ['م', 'التاريخ', 'الطالب', 'مبلغ الشراء ($)', 'نسبتي %', 'حصتي ($)', 'الحالة', 'ملاحظات'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . $row, $h);
            $sheet->getStyle($col . $row)->applyFromArray($headerFill);
            $sheet->getStyle($col . $row)->applyFromArray($border);
            $col++;
        }
        $row++;

        $index = 1;
        foreach ($activationPayments as $p) {
            $sheet->setCellValue('A' . $row, $index);
            $sheet->setCellValue('B' . $row, $p->created_at ? $p->created_at->format('Y-m-d') : '—');
            $sheet->setCellValue('C' . $row, $p->enrollment && $p->enrollment->student ? $p->enrollment->student->name : '—');
            $sheet->setCellValue('D' . $row, $p->enrollment ? round($p->enrollment->final_price ?? 0, 2) : 0);
            $sheet->setCellValue('E' . $row, $percentage);
            $sheet->setCellValue('F' . $row, round($p->amount, 2));
            $status = $p->status === 'paid' ? 'مدفوع' : ($p->status === 'approved' ? 'موافق عليه' : 'قيد المراجعة');
            $sheet->setCellValue('G' . $row, $status);
            $sheet->setCellValue('H' . $row, $p->notes ?? '—');
            $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($border);
            $row++;
            $index++;
        }

        $sheet->setCellValue('A' . $row, 'إجمالي أرباحي من هذه الاتفاقية');
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->setCellValue('F' . $row, round($totalEarned, 2));
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($summaryFill);
        $sheet->getStyle('A' . $row . ':H' . $row)->applyFromArray($border);
        $row++;

        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(14);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(18);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(16);
        $sheet->getColumnDimension('G')->setWidth(16);
        $sheet->getColumnDimension('H')->setWidth(24);

        $filename = 'تفعيلات_الطلاب_اتفاقية_' . ($agreement->agreement_number ?? $agreement->id) . '_' . now()->format('Y-m-d') . '.xlsx';

        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}

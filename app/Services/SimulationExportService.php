<?php

namespace App\Services;

use App\Models\Simulation;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\Writer\Word2007;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exports d'une simulation en PDF, Word et Excel.
 * Les données proviennent du modèle (calculées par LoanCalculator) : aucun recalcul ici.
 */
class SimulationExportService
{
    public const METHOD_LABELS = [
        'annuity' => 'Annuités constantes',
        'constant_capital' => 'Amortissement constant',
        'in_fine' => 'In fine',
    ];

    public function pdf(Simulation $simulation): Response
    {
        return Pdf::loadView('exports.simulation-pdf', $this->viewData($simulation))
            ->setPaper('a4')
            ->download($this->filename($simulation, 'pdf'));
    }

    public function excel(Simulation $simulation): BinaryFileResponse
    {
        $data = $this->viewData($simulation);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Amortissement');

        // En-tête paramètres
        $sheet->setCellValue('A1', 'Simulation de prêt — ' . config('app.name'));
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $paramRows = [
            ['Montant emprunté', $data['params']['amount']],
            ['Taux annuel', $data['params']['annual_rate'] . ' %'],
            ['Durée (mois)', $data['params']['duration_months']],
            ['Méthode', $data['methodLabel']],
            ['Assurance (annuelle)', $data['params']['insurance_rate'] . ' %'],
            ['Mensualité', $data['summary']['monthly_payment']],
            ['Total intérêts', $data['summary']['total_interest']],
            ['Coût total du crédit', $data['summary']['total_cost']],
            ['Total remboursé', $data['summary']['total_paid']],
        ];
        $rowIndex = 3;
        foreach ($paramRows as [$label, $value]) {
            $sheet->setCellValue("A$rowIndex", $label);
            $sheet->setCellValue("B$rowIndex", $value);
            $sheet->getStyle("A$rowIndex")->getFont()->setBold(true);
            if (is_numeric($value)) {
                $sheet->getStyle("B$rowIndex")->getNumberFormat()->setFormatCode('#,##0 "FCFA"');
            }
            $rowIndex++;
        }

        // Tableau d'amortissement
        $headerRow = $rowIndex + 1;
        $headers = ['N°', 'Date', 'Capital', 'Intérêts', 'Assurance', 'Mensualité', 'Solde restant'];
        foreach (array_values($headers) as $i => $header) {
            $sheet->setCellValue([$i + 1, $headerRow], $header);
        }
        $headerStyle = $sheet->getStyle([1, $headerRow, count($headers), $headerRow]);
        $headerStyle->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('6C4BC4');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $line = $headerRow + 1;
        foreach ($data['schedule'] as $row) {
            $sheet->setCellValue([1, $line], $row['period']);
            $sheet->setCellValue([2, $line], $row['date']);
            $sheet->setCellValue([3, $line], $row['principal']);
            $sheet->setCellValue([4, $line], $row['interest']);
            $sheet->setCellValue([5, $line], $row['insurance']);
            $sheet->setCellValue([6, $line], $row['total']);
            $sheet->setCellValue([7, $line], $row['balance']);
            $line++;
        }

        $sheet->getStyle([3, $headerRow + 1, 7, $line - 1])
            ->getNumberFormat()->setFormatCode('#,##0.00');
        $sheet->getStyle([1, $headerRow, 7, $line - 1])
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $path = tempnam(sys_get_temp_dir(), 'simulation-') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, $this->filename($simulation, 'xlsx'))->deleteFileAfterSend();
    }

    public function word(Simulation $simulation): BinaryFileResponse
    {
        $data = $this->viewData($simulation);

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(10);

        $section = $phpWord->addSection([
            'marginLeft' => Converter::cmToTwip(1.8),
            'marginRight' => Converter::cmToTwip(1.8),
        ]);

        $section->addText(
            'Simulation de prêt — ' . config('app.name'),
            ['bold' => true, 'size' => 18, 'color' => '6C4BC4'],
        );
        if ($simulation->title) {
            $section->addText($simulation->title, ['italic' => true, 'size' => 12]);
        }
        $section->addTextBreak();

        // Paramètres + résumé
        $summaryTable = $section->addTable([
            'borderSize' => 4,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 80,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
        $summaryRows = [
            ['Montant emprunté', $this->money($data['params']['amount'])],
            ['Taux annuel', str_replace('.', ',', (string) $data['params']['annual_rate']) . ' %'],
            ['Durée', $data['params']['duration_months'] . ' mois'],
            ['Méthode', $data['methodLabel']],
            ['Assurance annuelle', str_replace('.', ',', (string) $data['params']['insurance_rate']) . ' %'],
            ['Mensualité', $this->money($data['summary']['monthly_payment'])],
            ['Total des intérêts', $this->money($data['summary']['total_interest'])],
            ['Coût total du crédit', $this->money($data['summary']['total_cost'])],
            ['Total remboursé', $this->money($data['summary']['total_paid'])],
        ];
        foreach ($summaryRows as [$label, $value]) {
            $summaryTable->addRow();
            $summaryTable->addCell(4200)->addText($label, ['bold' => true]);
            $summaryTable->addCell(4800)->addText($value);
        }

        $section->addTextBreak();
        $section->addText("Tableau d'amortissement", ['bold' => true, 'size' => 13]);
        $section->addTextBreak();

        $table = $section->addTable([
            'borderSize' => 4,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 60,
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);
        $table->addRow(null, ['tblHeader' => true]);
        foreach (['N°', 'Date', 'Capital', 'Intérêts', 'Assurance', 'Mensualité', 'Solde restant'] as $header) {
            $table->addCell(1400, ['bgColor' => '6C4BC4'])
                ->addText($header, ['bold' => true, 'color' => 'FFFFFF']);
        }
        foreach ($data['schedule'] as $row) {
            $table->addRow();
            $table->addCell(700)->addText((string) $row['period']);
            $table->addCell(1400)->addText($row['date']);
            $table->addCell(1600)->addText($this->money($row['principal']));
            $table->addCell(1600)->addText($this->money($row['interest']));
            $table->addCell(1400)->addText($this->money($row['insurance']));
            $table->addCell(1600)->addText($this->money($row['total']));
            $table->addCell(1800)->addText($this->money($row['balance']));
        }

        $section->addTextBreak();
        $section->addText(
            'Document généré le ' . now()->format('d/m/Y') . ' — résultats indicatifs, ne constitue pas une offre de crédit.',
            ['italic' => true, 'size' => 8, 'color' => '888888'],
        );

        $path = tempnam(sys_get_temp_dir(), 'simulation-') . '.docx';
        (new Word2007($phpWord))->save($path);

        return response()->download($path, $this->filename($simulation, 'docx'))->deleteFileAfterSend();
    }

    private function viewData(Simulation $simulation): array
    {
        $params = $simulation->params;

        return [
            'simulation' => $simulation,
            'params' => $params,
            'summary' => $simulation->results['summary'],
            'schedule' => $simulation->results['schedule'],
            'methodLabel' => self::METHOD_LABELS[$params['method']] ?? $params['method'],
        ];
    }

    private function filename(Simulation $simulation, string $extension): string
    {
        $base = $simulation->title
            ? str($simulation->title)->slug()
            : 'simulation-pret-' . $simulation->id;

        return $base . '.' . $extension;
    }

    private function money(float|int $value): string
    {
        return number_format($value, 0, ',', ' ') . ' FCFA';
    }
}

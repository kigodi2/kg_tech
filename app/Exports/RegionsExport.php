<?php

namespace App\Exports;

use App\Models\Region;
use App\Models\DistrictCouncil;
use App\Models\School;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;

class RegionsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    private $regions;

    public function __construct()
    {
        $this->regions = $this->fetchRegions();
    }

    /**
     * Fetch regions data
     */
    private function fetchRegions()
    {
        try {
            return Region::orderBy('code')
                ->get()
                ->map(function ($region) {
                    return [
                        $region->code,
                        $region->name,
                        DistrictCouncil::where('region_id', $region->id)->count(),
                        School::where('region_id', $region->id)->count(),
                        $region->is_active ? 'Active' : 'Inactive',
                        optional($region->created_at)->format('Y-m-d') ?? '-',
                    ];
                });
        } catch (\Exception $e) {
            \Log::error('Failed to fetch regions for export: ' . $e->getMessage());
            return collect([]);
        }
    }

    /**
     * Get the regions collection
     */
    public function collection()
    {
        return $this->regions;
    }

    /**
     * Set the column headings
     */
    public function headings(): array
    {
        return [
            'Code',
            'Region Name',
            'Districts',
            'Schools',
            'Status',
            'Created Date',
        ];
    }

    /**
     * Style the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        try {
            // Style header row
            $sheet->getStyle('A1:F1')->applyFromArray([
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                    'size' => 12,
                ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1B5E3F'],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            // Set column widths
            $sheet->getColumnDimension('A')->setWidth(12);
            $sheet->getColumnDimension('B')->setWidth(25);
            $sheet->getColumnDimension('C')->setWidth(12);
            $sheet->getColumnDimension('D')->setWidth(12);
            $sheet->getColumnDimension('E')->setWidth(12);
            $sheet->getColumnDimension('F')->setWidth(15);

            // Freeze header row
            $sheet->freezePane('A2');

            // Style data rows if they exist
            $rows = count($this->regions);
            if ($rows > 0) {
                // Add borders and alternating colors to data rows
                for ($i = 2; $i <= $rows + 1; $i++) {
                    $bgColor = ($i % 2 == 0) ? 'F9F9F9' : 'FFFFFF';
                    
                    $sheet->getStyle('A' . $i . ':F' . $i)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => $bgColor],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'DDDDDD'],
                            ],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_LEFT,
                            'vertical' => Alignment::VERTICAL_CENTER,
                        ],
                    ]);

                    // Center align numeric columns
                    $sheet->getStyle('C' . $i . ':E' . $i)->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to style Excel worksheet: ' . $e->getMessage());
        }

        return [];
    }
}

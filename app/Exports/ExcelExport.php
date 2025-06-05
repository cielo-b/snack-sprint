<?php

namespace App\Exports;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;
class ExcelExport implements  FromCollection, WithHeadings, WithColumnWidths
{
    protected $titles;
    protected $data;

    public function __construct($titles, $data){
        $this->titles = $titles;
        $this->data = $data;
    }

    public function headings(): array {
        return $this->titles;
    }

    public function columnWidths(): array
    {
        return [
            'A' => 10,
            'B' => 10,
            'C' => 20,
            'D' => 10,
            'E' => 10,
            'F' => 20,
            'G' => 20,
            'H' => 20,
        ];
    }

    public function collection()
    {
        return $this->data;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class    => function(AfterSheet $event) {
                $cell = "A";
                foreach($this->titles as $title){
                   $event->sheet->getDelegate()->getColumnDimension($cell)->setWidth(50);
                   $cell++;
                }
            },
        ];
    }

}

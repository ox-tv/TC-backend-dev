<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MonetizationExport implements FromCollection, WithHeadings, WithMapping,
    ShouldAutoSize, WithStrictNullComparison, WithStyles
{
    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function collection()
    {
        return $this->users;
    }

    public function headings(): array
    {
        return [
            'Token Type',
            'Token Address',
            'Receiver',
            'Amount',
            'ID',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            "erc20",
            "0xc2132D05D31c914a87C6611C10748AEb04B58e8F",
            $row->wallet_address,
            $row->amount,
            $row->channel->name,
            $row->status_text,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}

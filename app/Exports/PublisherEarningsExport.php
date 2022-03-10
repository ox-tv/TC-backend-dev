<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PublisherEarningsExport implements FromCollection, WithHeadings, WithMapping,
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
            '#',
            'UserName',
            'Email',
            'Channel Name',
            'ETH Address',
            'Earning Status',
            'Earning Amount',
        ];
    }

    public function map($row): array
    {
        return [
            $row->id,
            $row->username,
            $row->email,
            $row->channelName,
            $row->eth_address,
            $row->earningStatus,
            $row->earningAmount,
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

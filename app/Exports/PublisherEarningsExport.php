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
            'Earning Status',
            'Earning Amount',
        ];
    }

    public function map($user): array
    {
        return [
            $user->id,
            $user->username,
            $user->email,
            $user->channelName,
            $user->earningStatus,
            $user->earningAmount,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}

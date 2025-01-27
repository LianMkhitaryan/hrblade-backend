<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithConditionalSheets;


use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class Import implements WithChunkReading,WithBatchInserts, WithMultipleSheets
{

    use WithConditionalSheets;
    /**
    * @param Collection $collection
    */


    public function chunkSize(): int
    {
        return 10;
    }

    public function batchSize(): int
    {
        return 10;
    }

    public function sheets(): array
    {
        return [
            'EN Questions' => '',
            'RU Questions' => '',
            'ALL Jobs' => '',
        ];
    }

    public function conditionalSheets(): array
    {
        return [
            'EN Questions' => '',
            'RU Questions' => '',
            'ALL Jobs' => '',
        ];
    }

    public function onUnknownSheet($sheetName)
    {
        info("Sheet {$sheetName} was skipped");
    }
}

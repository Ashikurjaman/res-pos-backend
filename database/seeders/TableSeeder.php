<?php

namespace Database\Seeders;

use App\Models\Table;
use Illuminate\Database\Seeder;

class TableSeeder extends Seeder
{
    public function run()
    {
        $tables = [
            ['table_number' => 'T-01', 'table_name' => 'Table 1'],
            ['table_number' => 'T-02', 'table_name' => 'Table 2'],
            ['table_number' => 'T-03', 'table_name' => 'Table 3'],
            ['table_number' => 'T-04', 'table_name' => 'Table 4'],
            ['table_number' => 'T-05', 'table_name' => 'Table 5'],
            ['table_number' => 'T-06', 'table_name' => 'Table 6'],
            ['table_number' => 'T-07', 'table_name' => 'Table 7'],
            ['table_number' => 'T-08', 'table_name' => 'Table 8'],
            ['table_number' => 'T-09', 'table_name' => 'Table 9'],
            ['table_number' => 'T-10', 'table_name' => 'Table 10'],
            ['table_number' => 'T-11', 'table_name' => 'Table 11'],
            ['table_number' => 'T-12', 'table_name' => 'Table 12'],
        ];

        foreach ($tables as $table) {
            Table::create($table);
        }
    }
}
<?php

namespace App\Libraries;

class SpreadsheetLoader
{
    public function __construct()
    {
        require_once APPPATH . 'Libraries/PhpSpreadsheet/Autoloader.php';
    }

    public function reader()
    {
        return new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    }

    public function readerCSV()
    {
        return new \PhpOffice\PhpSpreadsheet\Reader\Csv();
    }

    public function writerXlsx()
    {
        return new \PhpOffice\PhpSpreadsheet\Writer\Xlsx();
    }

    public function spreadsheet()
    {
        return new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }
}

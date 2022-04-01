<?php

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * @param string $xlsxFileName
 * @param $math
 * @return array
 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
 */
function parseGroups(string $xlsxFileName): array
{

    $ext = pathinfo($xlsxFileName, PATHINFO_EXTENSION);

    if ($ext == 'xlsm') {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($xlsxFileName);
    } else {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader(ucfirst($ext));
    }

// Load $inputFileName to a PhpSpreadsheet Object
    $spreadsheet = $reader->load($xlsxFileName);
    $worksheet = $spreadsheet->getActiveSheet();

// TODO: Add a const for Xlsx and Csv
    $dataEndRow = $worksheet->getHighestRow();
    $highestColumn = $worksheet->getHighestColumn();

    $headersArrayList = $worksheet->rangeToArray("A1:{$highestColumn}1", null, true, true, true);
    if (!isset($headersArrayList[1])) {
        echo 0;
    }
    $headersArray = $headersArrayList[1];
    $data = $worksheet->rangeToArray("A2:{$highestColumn}{$dataEndRow}", null, true, true, true);

    $pattern_find_group = '~[А-ЯЭЁ]{2,4}\-[1-6][0-9]{2}~u';
    $pattern_not = '~[абвгдеёжийклмнопртухцчшщъыьэюя\/\.]~u';

    $groups = [];
    foreach ($data as $line) {
        foreach ($line as $cell) {
            if (is_string($cell) && !preg_match($pattern_not, $cell) && preg_match($pattern_find_group, $cell, $math)) {
                $grp = strtr(trim($cell), [' ' => '']);

                if (strpos($grp, ',')) {
                    //разбираем ЭУЭ-123,123,123
                    $grp = explode(',', $grp);
                    $prefix = explode('-', $grp[0]);
                    $grp[0] = $prefix[1];
                    $prefix = $prefix[0];
                    foreach ($grp as $group) {
                        $groups[] = $prefix . '-' . $group;
                    }
                } else {
                    $groups[] = $grp;
                }
            }

        }
    }

    unset($reader, $spreadsheet, $worksheet);
    $groups = array_unique($groups);

    return $groups;
}

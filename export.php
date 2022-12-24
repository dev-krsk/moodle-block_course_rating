<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once("$CFG->libdir/phpspreadsheet/vendor/autoload.php");

$templateid = required_param('templateid', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT);
$returnurl = required_param('returnurl', PARAM_URL);


$course = get_course($courseid);
$context = context_course::instance($course->id);

require_login($course);

if (!has_capability('block/course_rating:download', $context)) {
    redirect($returnurl, 'Вы не зачислены как студент на данный курс!', 5, \core\output\notification::NOTIFY_ERROR);
}


$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$spreadsheet->getDefaultStyle()->getAlignment()->setWrapText(true);

$styleHeader = [
    'font' => [
        'bold' => true,
    ],
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'color' => [
            'argb' => 'FF808080',
        ],
    ],
];

$headers = [
    'Дата',
    'Время',
    'ФИО',
];
$width = ['14', '7', '40'];

foreach (block_course_rating_get_questions($templateid) as $question) {
    $headers[] = $question;
    $width[] = '30';
}
$headers[] = 'Отзыв';
$width[] = '30';

for ($i = 0, $l = sizeof($headers); $i < $l; $i++) {
    $sheet->setCellValueByColumnAndRow($i + 1, 1, $headers[$i]);
}

$sheet->getStyleByColumnAndRow(1, 1, sizeof($headers), 1)->applyFromArray($styleHeader);

for ($i = 0, $l = sizeof($width); $i < $l; $i++) {
    $sheet->getColumnDimensionByColumn($i + 1)->setWidth($width[$i]);
}

$data = block_course_rating_get_answers($courseid, $templateid);

for ($i = 0, $l = sizeof($data); $i < $l; $i++) { // row $i
    $j = 0;
    foreach ($data[$i] as $k => $v) { // column $j
        $sheet->setCellValueByColumnAndRow($j + 1, ($i + 1 + 1), $v);

        $j++;
    }
}

$styleData = [
    'alignment' => [
        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
        'wrapText' => true,
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
];
$sheet->getStyleByColumnAndRow(1, 1 + 1, sizeof($headers), sizeof($data) + 1)->applyFromArray($styleData);

$sheet->getStyleByColumnAndRow(1, 1); // поставим курсор на A1

$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . urlencode(get_string('pluginname', 'block_course_rating') . ' ' . $course->fullname . '-' . time()) . '.xlsx"');
$writer->save('php://output');





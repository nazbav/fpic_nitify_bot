<?php

$time_start = microtime(true);

ini_set('memory_limit', '512M');

use DiDom\Document;
use DiDom\Query;

require dirname(__DIR__) . '/vendor/autoload.php';
require __DIR__ . '/parser_xls.php';
require __DIR__ . '/functions.php';

//адрес расписания
const SCHEDULE_URL = 'https://www.vstu.ru/student/raspisaniya/zanyatiy/index.php?dep=fpik';
const EXAM_URL = 'https://www.vstu.ru/student/raspisaniya/exam/index.php?dep=fpik';
$temp_file = dirname(__DIR__) . '/cache/temp.xls';
$groups_file = dirname(__DIR__) . '//cache/groups.json';
$cache_file = dirname(__DIR__) . '/cache/cache.json';


$return = getScheduleCache($cache_file);

$groups_list_cache = getGroupsListCache($groups_file);

//если последняя выгрузка была позже чем пол часа назад, делаем новую
/**
 * @param string $temp_file
 * @param array $return
 * @return array
 * @throws \DiDom\Exceptions\InvalidSelectorException
 * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
 */
function ParseDocument($document_load, $groups, string $temp_file, array $return): array
{

    $document = new Document();

    $document->loadHtmlFile($document_load);

//ищем тело расписания
    $document = $document->find('.content-wrapper');

    /* @var $content \DiDom\Element */
    $content = min($document);

    $elements = '';

//ищем списки документов
    /* @var $h4 \DiDom\Element */
    $ul = $content->find('ul');

    foreach ($ul as $item => $element) {
        //ищем заголовки документов, формируем массив
        $grid = ['header' => $content->find('h4')[$item]->text(), 'items' => []];

        //ищем элементы списков
        foreach ($element->find('li') as $li) {
            /* @var $li \DiDom\Element */
            /* @var $a \DiDom\Element */

            //ищем ссылки
            $file = strtr($li->find('a')[0]->getAttribute('href'), ['../../../' => 'https://www.vstu.ru/']);

            //формируем массив
            $data = [
                'name' => $li->find('a')[0]->text(),
                'link' => $file
            ];

            $li->children()[0]->remove();

            //ищем даты изменения, удаляем лишнее из дат
            if (preg_match('~(.*): ([0-9\:\-\s\"]+)\)~ui', $li->text(), $math)) {
                $data['last_edit'] = date('d.m.Y H:i:s', strtotime($math[2]));
            }

            if (!isAcademicYear(strtotime($data['last_edit']))) {
                continue;
            }

            if (strpos($file, '.xls')) {
                if (strpos($file, '.xlsm')) {
                    $temp_file = dirname(__DIR__) . '/cache/temp.xlsm';
                }
                echo $file, PHP_EOL;
                //Сохраняем файл к себе
                file_put_contents($temp_file, file_get_contents($file));

                $data['hash'] = hash_file('md5', $temp_file);

                //Забираем группы
                $data['groups'] = parseGroups($temp_file);
                //сливаем группы в один массив
                $groups = array_merge($groups, $data['groups']);
            }

            /* @var $data \DiDom\Element */
            $grid['items'][] = $data;
        }
        $return[] = $grid;
    }

    unset($document);

    if (is_file($temp_file)) {
        unlink($temp_file);
    }


    return array($groups, $return);
}

if (true || isset($return['cache_datetime']) && $return['cache_datetime'] < time() - 300) {
    $return = [];
    $groups = [];

    $return2 = [];
    $groups2 = [];

    echo 'Run parsing ' . SCHEDULE_URL . PHP_EOL;
    list($groups, $return) = ParseDocument(SCHEDULE_URL, $groups, $temp_file, $return);

    echo 'Run parsing ' . EXAM_URL . PHP_EOL;
    list($groups2, $return2) = ParseDocument(SCHEDULE_URL, $groups, $temp_file, $return);


//удаляем временный файл

    //$return = array_merge_recursive($return, $return2);
    $groups = array_merge_recursive($groups, $groups2);

    foreach ($return as $item => $value) {
        foreach ($return2 as $item2 => $value2) {
            if ($item == $item2) {
                foreach ($value2['items'] as $item3 => $value3) {
                    $return[$item]['items'][] = $value3;
                }
            }
        }
        $return[$item]['items'] = unique_multidim_array($return[$item]['items'], 'hash');
    }

//Убираем лишнее если есть
    $groups = array_unique($groups);
//ищем курс в группе
    $pattern_find_course = '~[А-ЯЭЁ]{2,4}\-([1-6])~u';

    $groups_list = [];

//формируем список групп с указанием курса
    foreach ($groups as $group) {
        if (is_string($group) && preg_match($pattern_find_course, $group, $math)) {
            $groups_list[] = ['course' => $math[1], 'group' => $group];
        }
    }

//Сохраняем список групп отдельно

    $groups_list_cache = array_merge($groups_list_cache, $groups_list);

    $groups_list_cache = unique_multidim_array($groups_list_cache, 'group');

    file_put_contents($groups_file, json_encode($groups_list_cache, JSON_UNESCAPED_UNICODE));


// Добавляем время формирования кеша
    $return['cache_datetime'] = time();
    $time_end = microtime(true) - $time_start;
    $return['uptime'] = round($time_end, 4);
//пишем в файл
    file_put_contents($cache_file, json_encode($return, JSON_UNESCAPED_UNICODE));

//выводим
    echo 'end ' . $return['uptime'] . ' sec' . PHP_EOL;
}
//$return['groups_list'] = $groups_list_cache;

echo 'Parsing end ' . PHP_EOL;
//echo json_encode($return, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

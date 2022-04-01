<?php

/**
 * @param $array
 * @param $key
 * @return array
 */
function unique_multidim_array($array, $key)
{
    $temp_array = array();
    $i = 0;
    $key_array = array();

    foreach ($array as $val) {
        if (isset($val[$key]) && !in_array($val[$key], $key_array)) {
            $key_array[$i] = $val[$key];
            $temp_array[$i] = $val;
        }
        $i++;
    }
    return $temp_array;
}


/**
 * @param $groups_file
 * @return array|mixed
 */
function getGroupsListCache($groups_file)
{
    if (is_file($groups_file)) {
        $groups_list_cache = json_decode(file_get_contents($groups_file), true);
        if (is_array($groups_list_cache)) {
            return $groups_list_cache;
        }
    }
    return [];
}


/**
 * @param $cache_file
 * @return array|mixed
 */
function getScheduleCache($cache_file)
{
    if (is_file($cache_file)) {
        $return = json_decode(file_get_contents($cache_file), true);
        if (is_array($return)) {
            return $return;
        }
    }
    return [];
}

/**
 * @return string[]
 */
function getAcademicYear()
{
    $now = new DateTime();

    $year = $now->format('Y');
    return ($now->format('m') < 8) ? [($year - 1) . '-09-01', $year . '-08-30'] : [$year . '-08-29', ($year + 1) . '-08-30'];
}

/**
 * @param $time
 * @return bool
 */
function isAcademicYear($time): bool
{
    $academic_year = getAcademicYear();
    $academic_start = strtotime($academic_year[0]);
    $academic_end = strtotime($academic_year[1]);
    return ($academic_start < $time) && ($time < $academic_end);
}

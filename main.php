<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class Helpers
{
    public static function checkMergedCell($sheet, $cell)
    {
        foreach ($sheet->getMergeCells() as $cells) {
            if ($cell->isInRange($cells)) {
                // Cell is merged!
                return true;
            }
        }
        return false;
    }
}

class FormatDetector
{

    const FORMAT_GROUP = 'group';
    const FORMAT_DAY = 'day';
    const FORMAT_PAIR = 'pair';
    const FORMAT_PARITY = 'parity';
    const FORMAT_TYPE = 'type';
    const FORMAT_ROOM = 'room';
    const FORMAT_DATE = 'date';

    public static function detect($string)
    {
        if (static::isGroup(trim($string))) {
            return [
                'type' => static::FORMAT_GROUP,
                'value' => str_replace('/', '-', trim($string)),
            ];
        }

        if ($day = static::isDayOfWeek(mb_strtolower($string))) {
            return $day;
        }

        if ($pair = static::isPair($string)) {
            return $pair;
        }

        if ($parity = static::isParity(mb_strtolower($string))) {
            return $parity;
        }

        if ($type = static::isType($string)) {
            return $type;
        }

        if ($room = static::isRoom($string)) {
            return $room;
        }
        
        if ($date = static::isDate($string)) {
            return $date;
        }

        return false;
    }

    public static function isGroup($string)
    {
        $re = '/^([A-Я]{1,4})(\/)([a-я])(-)(\d){1,3}/u';
        return preg_match($re, $string);
    }

    public static function isDayOfWeek($string)
    {
        $transDays = [
            'понедельник' => 'monday',
            'вторник' => 'tuesday',
            'среда' => 'wednesday',
            'четверг' => 'thursday',
            'пятница' => 'friday',
            'суббота' => 'saturday',
            'понед.' => 'monday',
        ];

        $patterns = [
            'понедельник',
            'вторник',
            'среда',
            'четверг',
            'пятница',
            'суббота',
            'понед.',
        ];

        $string = str_replace(' ', '', $string);

        foreach ($patterns as $pattern) {
            if (mb_strpos($string, $pattern) === false) {
                continue;
            }

            return [
                'type' => static::FORMAT_DAY,
                'value' => $transDays[$pattern],
            ];

        }

        return false;
    }

    public static function isPair($string)
    {
        if (mb_strpos($string, 'пара')) {
            return [
                'type' => static::FORMAT_PAIR,
                'value' => (int)str_replace('пара', '', $string),
            ];
        }

        return false;
    }

    public static function isParity($string)
    {
        $str = trim($string);
        if (mb_strpos($str, 'нечет') === 0) {
            return [
                'type' => static::FORMAT_PARITY,
                'value' => 'odd',
            ];
        }

        if (mb_strpos($str, 'чет') === 0) {
            return [
                'type' => static::FORMAT_PARITY,
                'value' => 'even',
            ];
        }

        return false;
        // if (in_array(trim($string), [
        //     'чет.',
        //     'нечет.',
        //     'чет',
        //     'нечет',
        //     'четн',
        //     'четн.',
        // ])) {
        //     return [
        //         'type' => static::FORMAT_PARITY,
        //         'value' => mb_strpos($string, 'нечет') !== false? 'odd' : 'even',
        //     ];
        // }
    }

    public static function isType($string)
    {
        $string = trim($string);

        if ($string == 'ПЗ') {
            return [
                'type' => static::FORMAT_TYPE,
                'value' => 'practics'
            ];
        }

        if ($string == 'Л') {
            return [
                'type' => static::FORMAT_TYPE,
                'value' => 'lecture'
            ];
        }

        if ($string == 'ЛЗ') {
            return [
                'type' => static::FORMAT_TYPE,
                'value' => 'lab'
            ];
        }

        return false;
    }

    public static function isRoom($string)
    {
        $string = trim($string);

        if (mb_strpos($string, 'ДИСТ') !== false) {
            return [
                'type' => static::FORMAT_ROOM,
                'value' => 'remote',
            ];
        }

        if (preg_match('/^(\d{0,4})(.)$/u', $string)) {
            return [
                'type' => static::FORMAT_ROOM,
                'value' => $string,
            ];
        }

        if (preg_match('/^(.)(-)(\d{0,4})$/u', $string)) {
            return [
                'type' => static::FORMAT_ROOM,
                'value' => $string,
            ];
        }
    }

    public static function isDate($string)
    {
        $string = trim($string);

        if (preg_match('/^(\d){1,2}\/(\d){1,2}\/(\d){1,4}$/u', $string)) {
            return [
                'type' => static::FORMAT_DATE,
                'value' => $string,
            ];
        }
    }

    public static function parseSubject($string)
    {
        $string = trim($string);

        $string = str_replace('ст. пр.', 'ст.пр.', $string);
        $string = str_replace('ст. пр.', 'ст.пр.', $string);

        $re = '/(?<lecturer>пр\. )?(?<seniorLecturer>ст\.пр\. )?(?<docent>доц\.( )?)?(?<professor>проф\. )?(?<assistant>асс\. )?((?<surname>([А-я]){3,20})( ){1,5}([A-я]\.)( )?([A-я]\.))/um';
        preg_match_all($re, $string, $lecturersResults, PREG_SET_ORDER, 0);
        
        $lecturers = [];

        foreach ($lecturersResults as $result) {
            $state = '';

            foreach (['seniorLecturer', 'professor', 'docent', 'assistant', 'lecturer'] as $stateVariant) {
                if ($result[$stateVariant]) {
                    $state = $stateVariant;
                }
            }

            $lecturers[] = [
                'state' => $state,
                'surname' => $result['surname'],
                'initials' => $result[9] . $result[12],
                'fullName' => $result[0],
            ];

            $string = str_replace($result[0], '', $string);
        }

        $re = '/(?<seniorLecturer>ст\.пр\. )?(?<docent>доц\. )?(?<professor>проф\. )?(?<assistant>асс\. )?((?<surname>([А-я]){3,20}))/um';

        preg_match_all($re, $string, $lecturersResults, PREG_SET_ORDER, 0);

        foreach ($lecturersResults as $result) {
            $state = '';

            foreach (['seniorLecturer', 'professor', 'docent', 'assistant'] as $stateVariant) {
                if ($result[$stateVariant]) {
                    $state = $stateVariant;
                }
            }

            if (!$state) {
                continue;
            }

            $lecturers[] = [
                'state' => $state,
                'surname' => $result['surname'],
                'fullName' => $result[0],
            ];

            $string = str_replace($result[0], '', $string);
        }

        $rooms = [];

        $re = '/(\d{1,4})([А-Я])/um';
        preg_match_all($re, $string, $roomsResults, PREG_SET_ORDER, 0);

        foreach ($roomsResults as $result) {
            $rooms[] = $result[0];
            $string = str_replace($result[0], '', $string);
        }



        $re = '/([А-Я])( )(\d{1,4})/um';
        preg_match_all($re, $string, $roomsResults, PREG_SET_ORDER, 0);

        foreach ($roomsResults as $result) {
            $rooms[] = $result[0];
            $string = str_replace($result[0], '', $string);
        }



        $re = '/(каб\. )(\d){1,4}/um';
        preg_match_all($re, $string, $roomsResults, PREG_SET_ORDER, 0);

        foreach ($roomsResults as $result) {
            $rooms[] = $result[0];
            $string = str_replace($result[0], '', $string);
        }




        $re = '/([А-Я])(-)\d{1,4}/um';
        preg_match_all($re, $string, $roomsResults, PREG_SET_ORDER, 0);

        foreach ($roomsResults as $result) {
            $rooms[] = $result[0];
            $string = str_replace($result[0], '', $string);
        }

        $string = str_replace(',', '', $string);
        
        $fParts = [];
        foreach (explode(' ', $string) as $part) {
            if (trim($part)) {
                $fParts[] = $part;
            }
        }
        $string = implode(' ', $fParts);

        if (mb_strpos($string, 'ДИСТ.')) {
            $rooms[] = 'remote';
            $string = str_replace('ДИСТ.', '', $string);
        }

        return [
            'subject' => trim($string),
            'rooms' => $rooms,
            'lecturers' => $lecturers,
            'groups' => [],
            'pair' => '',
            'parity' => '',
            'type' => '',
            'day' => '',
            'date' => '',
        ];
    }
}

class Parser
{
    public $spreadsheet;
    public $file;
    public $institutionName;

    public $allowDistantGroups = false;
    public $writeSheetFile = false;

    public function init($file)
    {
        $this->file = $file;
        $this->institutionName = explode('___', basename($this->file))[0];
        $this->spreadsheet = IOFactory::load($file);
    }

    public function toArray()
    {
        $rows = [];
        foreach ($this->spreadsheet->getActiveSheet()->getRowIterator() as $rowNumber => $row) {
            echo '.';
            $cellIterator = $row->getCellIterator();
            // $cellIterator->setIterateOnlyExistingCells(true); // This loops through all cells,
            $cells = [];
            foreach ($cellIterator as $cell) {    
                $value = null;            
                if ($cell->isInMergeRange() && !$cell->isMergeRangeValueCell()) {
                    $range = explode(':', $cell->getMergeRange());
                    $value = (string)$this->spreadsheet->getActiveSheet()->getCell($range[0])->getValue();
                    $cells[] = $value;
                } else {
                    $value = (string)$cell->getValue();
                    $cells[] = $value;
                }

                if ($value && mb_strpos(mb_strtolower($value), 'экзамен') !== false) {
                    die('Not a schedule. Should not be processed!' . PHP_EOL);
                }
            }            
            $rows[] = $cells;
        }
        echo PHP_EOL;

        return $rows;
    }

    public function iterate()
    {
        foreach ($this->spreadsheet->getSheetNames() as $index => $name) {
            echo $name . PHP_EOL;
            $this->spreadsheet->setActiveSheetIndex($index);
            
            $rows = $this->toArray();

            $groups = [];
            $currentDay = '';
            $currentPair = '';
            $currentParity = '';
            $currentDate = '';

            $currentSubjectEntity = null;
            $currentSubject = '';

            $subjects = [];            

            foreach ($rows as $row) {
                foreach ($row as $index => $cell) {
                    $format = FormatDetector::detect($cell);
                        
                    if ($cell && count($groups) && in_array($index, array_keys($groups)) 
                        && !FormatDetector::isGroup(trim($cell))) {
                        if (!$currentSubjectEntity) {
                            $currentSubjectEntity = FormatDetector::parseSubject($cell);
                            $currentSubject = $cell;
                        }

                        if ($currentSubject != $cell) {
                            $subjects[] = $currentSubjectEntity;

                            $currentSubjectEntity = FormatDetector::parseSubject($cell);
                            $currentSubject = $cell;
                        }

                        if (!in_array($groups[$index], $currentSubjectEntity['groups'])) {
                            $currentSubjectEntity['groups'][] = $groups[$index];
                        }

                        $currentSubjectEntity['parity'] = $currentParity;
                        $currentSubjectEntity['pair'] = $currentPair;
                        $currentSubjectEntity['day'] = $currentDay;
                        $currentSubjectEntity['date'] = $currentDate;
                    } else {
                        if (!$format) {
                            continue;
                        }
                    }

                    if ($format['type'] == FormatDetector::FORMAT_GROUP) {
                        if (mb_strpos($format['value'], ',') !== false) {
                            $arr = explode(',', $format['value']);
                            foreach ($arr as $i => $groupItem) {
                                $groups[$index + (($i > 0 && $row[$index + $i] == $cell) ? $i : 0)] = $groupItem;
                            }
                        } else {
                            $groups[$index] = $format['value'];    
                        }                        
                    }

                    if ($format['type'] == FormatDetector::FORMAT_DAY) {
                        $currentDay = $format['value'];
                    }

                    if ($format['type'] == FormatDetector::FORMAT_PAIR) {
                        $currentPair = $format['value'];
                    }

                    if ($format['type'] == FormatDetector::FORMAT_PARITY) {
                        $currentParity = $format['value'];
                    }

                    if ($format['type'] == FormatDetector::FORMAT_DATE) {
                        $currentDate = $format['value'];
                    }

                    if ($format['type'] == FormatDetector::FORMAT_TYPE && $currentSubjectEntity) {
                        $currentSubjectEntity['type'] = $format['value'];
                    }

                    if ($format['type'] == FormatDetector::FORMAT_ROOM && $currentSubjectEntity) {
                        $currentSubjectEntity['rooms'][] = $format['value'];
                    }
                }

                if ($currentSubjectEntity) {
                    $subjects[] = $currentSubjectEntity;
                    $currentSubjectEntity = null;
                }
            }

            $institutionData  = [
                'name' => $this->institutionName,
                'groups' => [],
            ];

            foreach($groups as $group) {

                if (in_array($group, $institutionData['groups'])) {
                    continue;
                }

                if (!$this->allowDistantGroups && mb_substr($group, -2, 2) == '-з') {
                    continue;
                }

                $groupJson = [
                    'odd' => [
                        'monday' => [],
                        'tuesday' => [],
                        'wednesday' => [],
                        'thursday' => [],
                        'friday' => [],
                        'saturday' => [],                        
                    ],
                    'even' => [
                        'monday' => [],
                        'tuesday' => [],
                        'wednesday' => [],
                        'thursday' => [],
                        'friday' => [],
                        'saturday' => [],                        
                    ],
                    'none' => [
                        'monday' => [],
                        'tuesday' => [],
                        'wednesday' => [],
                        'thursday' => [],
                        'friday' => [],
                        'saturday' => [],   
                    ]
                ];

                foreach($subjects as $subject) {
                    if (!in_array($group, $subject['groups'])) {
                        continue;
                    }
                    
                    if (!isset($groupJson[$subject['parity']])) {
                        $subject['parity'] = 'none';
                    }

                    if (!$subject['day'] || !isset($groupJson[$subject['parity']][$subject['day']])) {
                        throw new Exception('day not found.' . print_r($subject));
                    }

                    if ($subject['parity'] == 'none') {
                        echo "Warning! Parity not detected!" . PHP_EOL;
                    }

                    $groupJson[$subject['parity']][$subject['day']][] = $subject;
                }
                if (!in_array($group, $institutionData['groups'])) {
                    $institutionData['groups'][] = $group;
                }
                
                file_put_contents('./output/' . $group . '.json', json_encode($groupJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }

            $indexFile = [];

            if (file_exists('./output/index.json')) {
                $indexFile = json_decode(file_get_contents('output/index.json'), true);
            }

            $indexFile[$this->institutionName] = [
                'name' => $this->institutionName,
                'groups' => array_merge(
                    (isset($indexFile[$this->institutionName]) ? $indexFile[$this->institutionName]['groups'] : []),
                    $institutionData['groups']
                ),
            ];

            file_put_contents('./output/index.json', json_encode($indexFile, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            if ($this->writeSheetFile) {
                $dir = 'output/' . basename($this->file);
                if (!file_exists($dir)) {
                    mkdir($dir);
                }

                file_put_contents("$dir/$name.json", json_encode($subjects, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
        }
    }
}

$parser = new Parser();
$parser->init($argv[1]);
$parser->iterate();

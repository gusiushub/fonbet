<?php

/**
 * @param $from
 * @param $to
 * @param $file
 * @return int
 */
function run($from,$to,$file)
{
    for ($i=$from;$i<$to;$i++) {
        $percentage = $i / $to * 100;
        showProgressBar($percentage, 2);
        $original[] = generateCsv(getItem($i),$i);
    }
    setCsv($original,$file);
    print PHP_EOL;
    print "Файл обновлен!" . PHP_EOL;
//    echo "\n\r";
//    echo 'Файл обновлен';
    return true;
}

function cron()
{
    $id = getFirstItemId();
    $k = $id-3;
    for ($i=$id;$i>$k;$i--){
        $original[] = generateCsv(getItem($i), $i);
    }
    setCsv($original, 'monitorCron.csv');
//    $handle = @fopen('./monitor.txt', "r");
//    if ($handle) {
//        $i = 0;
//        while (!feof($handle)) {
//            $buffer = fgets($handle);
//            $original[] = generateCsv(getItem($buffer), $i);
//            $i++;
//        }
//        setCsv($original, './monitorCron.csv');
//        fclose($handle);
//    }
}

/**
 * @param array $item
 * @param $i
 * @return mixed
 */
function generateCsv($item, $i)
{
    if (!$item) {
        echo 'Ошибка при запросе события';
        echo "\n\r";
        return false;
    }
    $State = (int)$item['State'];
    if ($item['IsBlocked']){
        $status = 'Отменен';
    }elseif ($State==3){
        $status = 'Прием ставок';
        if (!searchStr($item['Id'],'monitor.txt')){
            setMonitorItem($item['Id']);
        }
    }elseif ($State==2){
        $status = 'Ожидание';
        if (!searchStr($item['Id'],'monitor.txt')){
            setMonitorItem($item['Id']);
        }
    }elseif($State==1){
        $status = 'Разыгран';
        if (searchStr($item['Id'],'monitor.txt')){
            delStr($item['Id'],'monitor.txt');
        }
    }

    $original[$i] = [
        $item['Id'],
        $status,
        $item['Pool'],
        $item['Jackpot'],
        $item['Complexity'],
//        $item['LocalPool'],
//        $item['MaxPrize'],
//        $item['CouponCount'],
    ];

    if (isset($item['Details']['Events'])) {
        $events = $item['Details']['Events'];
        foreach ($events as $event) {
            if (preg_match('#\((.*?)\)#', $event['Date'], $matches)) {
                $EventDate = date('Y-m-d H:m', (int)substr($matches[1], 0, -3));
            } else {
                continue;
            }
            array_push(
                $original[$i],
//                $event['Id'],
                $EventDate,
//                $event['Championships'][0]['Value'],
                $event['Names'][0]['Value'],
                $event['Score'],
                $event['ResultCode'],
                $event['UserWin1']['Percentage'],
                $event['UserWin1']['Probability'],
                $event['UserDraw']['Probability'],
                $event['UserDraw']['Probability'],
                $event['UserWin2']['Percentage'],
                $event['UserWin2']['Probability']
            );
        }
    }

//    if (isset($item['Details']['DefaultResult']['DrawingResults'])){
//        $table = $item['Details']['DefaultResult']['DrawingResults'];
//        foreach ($table as $column) {
//            array_push(
//                $original[$i],
//                $column['Result'],
//                $column['Percent'],
//                $column['Pool'],
//                $column['Count'],
//                $column['Amount']['Value'],
//                $column['K']
//            );
//        }
//    }

    return $original[$i];
}

function getFirstItemId()
{
    $item = getFirstItem();
    $id = $item['Items'][0]['Id'];
    return $id;
}

function getFirstItem()
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://clientsapi11.bkfon-resource.ru/superexpress-info/DataService.svc/SelectDrawings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"sp\":{\"StartFrom\":0,\"Count\":20,\"SortField\":\"Expired\",\"SortDir\":\"DESC\",\"Culture\":\"ru-RU\",\"TimeZoneId\":\"\",\"TimeZoneOffset\":-180,\"State\":[2]}}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Origin: https://www.fonbet.ru';
    $headers[] = 'Accept-Encoding: gzip, deflate, br';
    $headers[] = 'Accept-Language: ru,en;q=0.9';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/72.0.3626.121 YaBrowser/19.3.2.177 Yowser/2.5 Safari/537.36';
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'Accept: application/json, text/plain, */*';
    $headers[] = 'Referer: https://www.fonbet.ru/superexpress-info/?locale=ru&pageDomain=https://www.fonbet.ru';
    $headers[] = 'Connection: keep-alive';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error:' . curl_error($ch);
    }
    curl_close ($ch);

    $result = json_decode($result, true);
    return $result['d'];
}

/**
 * @param $searchthis
 * @param $file
 * @return bool
 */
function searchStr($searchthis, $file)
{
    $searchthis = (string)$searchthis;

    $matches = array();

    $handle = @fopen($file, "r");
    if ($handle)
    {
        while (!feof($handle))
        {
            $buffer = fgets($handle);
            if(strpos($buffer, $searchthis) !== FALSE)
                $matches[] = $buffer;
        }
        fclose($handle);
    }
    if (empty($matches)){
        return false;
    }
    return true;
}

/**
 * @param $string
 * @param $file
 */
function delStr($string, $file)
{
    $DELETE = (string)$string;

    $data = file($file);

    $out = array();

    foreach($data as $line) {
        if(trim($line) != $DELETE) {
            $out[] = $line;
        }
    }

    $fp = fopen($file, "w+");
    flock($fp, LOCK_EX);
    foreach($out as $line) {
        fwrite($fp, $line);
    }
    flock($fp, LOCK_UN);
    fclose($fp);
}


/**
 * @return bool
 */
function getCount()
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://clientsapi11.bkfon-resource.ru/superexpress-info/DataService.svc/SelectDrawings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"sp\":{\"StartFrom\":0,\"Count\":20,\"SortField\":\"Expired\",\"SortDir\":\"DESC\",\"Culture\":\"ru-RU\",\"TimeZoneId\":\"\",\"TimeZoneOffset\":-180,\"State\":[0,1]}}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Accept: application/json, text/plain, */*';
    $headers[] = 'Referer: https://www.fonbet.ru/superexpress-info/?locale=ru&pageDomain=https://www.fonbet.ru';
    $headers[] = 'Origin: https://www.fonbet.ru';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36';
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }
    curl_close ($ch);
    $result = json_decode($result, true);
    return $result['d']['Summary']['TotalCount'];
}

/**
 * @param int $count
 * @return bool|mixed|string
 */
function getAllItems($count = 20)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://clientsapi11.bkfon-resource.ru/superexpress-info/DataService.svc/SelectDrawings');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"sp\":{\"StartFrom\":0,\"Count\":$count,\"SortField\":\"Expired\",\"SortDir\":\"ASC\",\"Culture\":\"ru-RU\",\"TimeZoneId\":\"\",\"TimeZoneOffset\":-180,\"State\":[0,1]}}");
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"sp\":{\"StartFrom\":0,\"Count\":$count,\"SortField\":\"Expired\",\"SortDir\":\"DESC\",\"Culture\":\"ru-RU\",\"TimeZoneId\":\"\",\"TimeZoneOffset\":-180,\"State\":[0,1]}}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');
    $headers = array();
    $headers[] = 'Accept: application/json, text/plain, */*';
    $headers[] = 'Referer: https://www.fonbet.ru/superexpress-info/?locale=ru&pageDomain=https://www.fonbet.ru';
    $headers[] = 'Origin: https://www.fonbet.ru';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36';
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return 'Error:' . curl_error($ch);
    }
    curl_close($ch);
    $result = json_decode($result, true);
    return $result;
}

/**
 * @param $id
 * @return bool|mixed|string
 */
function getItem($id)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://clientsapi11.bkfon-resource.ru/superexpress-info/DataService.svc/GetDrawing');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "{\"id\":$id}");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Accept: application/json, text/plain, */*';
    $headers[] = 'Referer: https://www.fonbet.ru/superexpress-info/?locale=ru&pageDomain=https://www.fonbet.ru';
    $headers[] = 'Origin: https://www.fonbet.ru';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.103 Safari/537.36';
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
        return false;
    }
    curl_close ($ch);
    $result = json_decode($result, true);
    return $result['d'];
}

/**
 * @param $list
 * @param $file
 * @return void
 */
function setCsv($list,$file)
{
    $fp = fopen($file, 'w');

    foreach ($list as $fields) {
                fputcsv($fp, $fields, ';');
    }
    fclose($fp);
}

/**
 * @param $string
 */
function setMonitorItem($string)
{
    $fp = fopen('monitor.txt', 'a');
    fwrite($fp, $string . PHP_EOL);
    fclose($fp);
}

/**
 * Display a progress bar in the CLI. This will dynamically take up the full width of the
 * terminal and if you keep calling this function, it will appear animated as the progress bar
 * keeps writing over the top of itself.
 * @param float $percentage - the percentage completed.
 * @param int $numDecimalPlaces - the number of decimal places to show for percentage output string
 */
function showProgressBar($percentage, int $numDecimalPlaces)
{
    $percentageStringLength = 4;
    if ($numDecimalPlaces > 0)
    {
        $percentageStringLength += ($numDecimalPlaces + 1);
    }

    $percentageString = number_format($percentage, $numDecimalPlaces) . '%';
    $percentageString = str_pad($percentageString, $percentageStringLength, " ", STR_PAD_LEFT);

    $percentageStringLength += 3; // add 2 for () and a space before bar starts.

    $terminalWidth = 80;
    $barWidth = $terminalWidth - ($percentageStringLength) - 2; // subtract 2 for [] around bar
    $numBars = round(($percentage) / 100 * ($barWidth));
    $numEmptyBars = $barWidth - $numBars;

    $barsString = '[' . str_repeat("=", ($numBars)) . str_repeat(" ", ($numEmptyBars)) . ']';

    echo "($percentageString) " . $barsString . "\r";
}
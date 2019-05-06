<?php
set_time_limit(0);

require __DIR__.'/function.php';

$handle = @fopen('./monitor.txt', "r");
if ($handle)
{
    $i=0;
    while (!feof($handle))
    {
        $buffer = fgets($handle);
        $item = getItem($buffer);
        if (!$item) {
            echo 'Ощибка при запросе события';
            continue;
        }
        $IsBlocked = $item['IsBlocked'];
        if (!$IsBlocked){
            delStr($item['Id'],'./monitor.txt');
        }
        //общие данные
        //сложность
        $Complexity[] = $item['Complexity'];
        //кол-во купонов
        $CouponCount[] = $item['CouponCount'];
        $id[] = $item['Id'];
        $Jackpot[] = $item['Jackpot'];
        $LocalPool[] = $item['LocalPool'];
        $Pool[] = $item['Pool'];
        $MaxPrize[] = $item['MaxPrize'];
        $info = array($id,$Complexity, $CouponCount, $Jackpot, $LocalPool, $Pool, $MaxPrize);
        //таблица
        $table = $item['Details']['DefaultResult']['DrawingResults'];
        $j = 0;
        foreach ($table as $column) {
            $tables[$i][$j][] = $column['Result'];
            $tables[$i][$j][] = $column['Percent'];
            $tables[$i][$j][] = $column['Pool'];
            $tables[$i][$j][] = $column['Count'];
            $tables[$i][$j][] = $column['Amount']['Value'];
            $tables[$i][$j][] = $column['K'];
            $j++;
        }
        $events = $item['Details']['Events'];
        $j = 0;
        foreach ($events as $event) {
            $match[$i][$j][] = $event['Id'];
            $match[$i][$j][] = $event['Championships'][0]['Value'];
            if (preg_match('#\((.*?)\)#', $event['Date'], $matches)) {
                $match[$i][$j][] = date('Y-m-d H:m', (int)substr($matches[1], 0, -3));
            }
            $match[$i][$j][] = $event['Names'][0]['Value'];
            $match[$i][$j][] = $event['ResultCode'];
            $match[$i][$j][] = $event['Score'];
            $j++;
        }
        $i++;
    }
    $csv = array(array($info), $tables, $match);

    setCsv($csv,'./monitorCron.csv');
    fclose($handle);
}

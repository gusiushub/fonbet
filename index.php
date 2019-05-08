<?php
set_time_limit(0);
//$start = microtime(true);

require __DIR__.'/function.php';

echo " _        _        _  -            _             \n";
echo "| |_ ___ | |_ ___ | | -  ___  ___ | |_ ___   __    \n";
echo "|  _| . ||  _| .'|| || ||- _|| .'||  _| . ||  _|   \n";
echo "|_| |___||_| |__,||_||_||___||__,||_| |___||_|     \n";
echo "\n";

switch ($argv[1]) {
    case 'run':
        $from = 1;
        $to = getFirstItemId();
        $file ='file.csv';
        run($from,$to,$file);
        break;
    case 'cron':
        cron();
        break;

    default:
        echo "Ошибка\n";
        break;
}
echo "\n";
//$finish = microtime(true);
//$delta = $finish - $start;
echo "\r\n";
//echo $delta . ' сек.';

return 0;


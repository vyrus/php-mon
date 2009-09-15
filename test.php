<?php
    
    require_once 'init.php';
    
    $mon_file = MONITORS . DS . 'temperature-test';
    
    $storage = Monitor_Storage_Array::create()
        ->setFile($mon_file)
    ;
    
    $monitor = Monitor::create($mon_file)
        ->setStorage($storage)
    ;
    
    $time = 0;
    $consol_period = 5 * Time::MINUTE;
    $store_period = Time::DAY;
    $max_stored_values = $store_period / $consol_period;
        
    $monitor
        ->setConsolidationPeriod($consol_period)
        ->setMaxStoredValues($max_stored_values)
        ->setLastConsolidationTime($time)
        ->setLastIndicatorsUpdate($time)
        ->init()
    ;
        
    $temperature = array(
        1 => 40,
        2 => 45,
        3 => 50
    );
        
    $monitor->open();
        
        for ($i = 1; $i <= 3 * 1440; $i++) {
            $monitor->update($time + $i * Time::MINUTE, rand(40, 50));
        }
        
    $monitor->close();
    
    $monitor->open();
        print_r($monitor->show());
    $monitor->close();
    
?>
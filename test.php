<?php
    
    require_once 'init.php';
    
    $mon_file = MONITORS . DS . 'temperature-test';
    
    $storage = Monitor_Storage_Array::create()
        ->set_file($mon_file)
    ;
    
    $monitor = Monitor::create($mon_file)
        ->set_storage($storage)
    ;
    
    $time = 0;
    $consol_period = 5 * Time::MINUTE;
    $store_period = Time::DAY;
    $max_stored_values = $store_period / $consol_period;
        
    $monitor
        ->set_сonsolidation_period($consol_period)
        ->set_max_stored_values($max_stored_values)
        ->set_last_consolidation_time($time)
        ->set_last_indicators_update($time)
        ->init()
    ;
        
    $monitor->open();
        for ($i = 1; $i <= 3 * 1440; $i++) {
            $monitor->update($time + $i * Time::MINUTE, rand(40, 50));
        }
    $monitor->close();
    
    $monitor->open();
        print_r($monitor->show());
    $monitor->close();
    
?>
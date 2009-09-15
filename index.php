<?php
    
    require_once 'init.php';
    
    $mon_file = MONITORS . DS . 'temperature';
    
    $action = 'create';
    $action = 'update';
    $action = 'show';
    
    $storage = Monitor_Storage_Array::create()
        ->setFile($mon_file)
    ;
    
    if ('create' == $action)
    {
        $consol_period = 5 * Time::SECOND;
        $store_period = 365 * Time::DAY;
        $max_stored_values = $store_period / $consol_period;
        
        Monitor::create()
            ->setConsolidationPeriod($consol_period)
            ->setMaxStoredValues($max_stored_values)
            ->setLastConsolidationTime(time())
            ->setLastSlotsUpdate(time())
            ->setStorage($storage)
            ->init()
        ;
    }
    elseif ('update' == $action)
    {
        $monitor = Monitor::create($mon_file)
            ->setStorage($storage)
        ;
        $monitor->open();
        $monitor->update(time(), rand(40, 55));
        $monitor->close();
    }
    elseif ('show' == $action)
    {
        $monitor = Monitor::create($mon_file)
            ->setStorage($storage)
        ;
        $monitor->open();
        print_r($monitor->show());
        $monitor->close();
    }
    
?>
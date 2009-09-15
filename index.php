<?php
    
    require_once 'init.php';
    
    $mon_file = MONITORS . DS . 'temperature';
    
    $action = 'create';
    $action = 'update';
    
    if ('create' == $action)
    {
        $consol_period = 5 * Time::MINUTE;
        $store_period = 365 * Time::DAY;
        $max_stored_values = $store_period / $consol_period;
        
        $storage = Monitor_Storage_Rrd::create()
            ->setFile($mon_file)
        ;
        
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
        $storage = Monitor_Storage_Rrd::create()
            ->setFile($mon_file)
        ;
        
        Monitor::create($mon_file)
            ->setStorage($storage)
            ->open()
            ->update(time(), '47')
            ->close()
        ;
    }

?>
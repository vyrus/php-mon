<?php
    
    require_once 'init.php';
    
    $mon_file = MONITORS . DS . 'temperature';
    
    $action = 'create';
    $action = 'update';
    $action = 'show';
    
    $storage = Monitor_Storage_Array::create()
        ->set_file($mon_file)
    ;
    
    if ('create' == $action)
    {
        $consol_period = 5 * Time::SECOND;
        $store_period = 365 * Time::DAY;
        $max_stored_values = $store_period / $consol_period;
        
        Monitor::create()
            ->set_consolidation_period($consol_period)
            ->set_max_stored_values($max_stored_values)
            ->set_last_consolidation_time(time())
            ->set_last_indicators_update(time())
            ->set_storage($storage)
            ->init()
        ;
    }
    elseif ('update' == $action)
    {
        $monitor = Monitor::create($mon_file)
            ->set_storage($storage);
            
        $monitor->open();
            $monitor->update(time(), rand(40, 55));
        $monitor->close();
    }
    elseif ('show' == $action)
    {
        $monitor = Monitor::create($mon_file)
            ->set_storage($storage);
            
        $monitor->open();
            print_r($monitor->show());
        $monitor->close();
    }
    
?>
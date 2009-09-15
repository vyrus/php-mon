<?php
    
    require_once 'init.php';
    
    $mon_file = MONITORS . DS . 'default';
    
    $action = 'create';
    //$action = 'update';
    
    if ('create' == $action)
    {
        $consol_period = 5 * Time::MINUTE;
        $store_period = Time::DAY;
        $num_stored_values = $store_period / $consol_period;
        
        Monitor::create()
            ->setLastConsolidationTime(time())
            ->setConsolidationPeriod($consol_period)
            ->setNumStoredValues($num_stored_values)
            ->freeze($mon_file)
        ;
    }
    elseif ('update' == $action)
    {
        Monitor::thaw($mon_file)
            ->update(time(), '47')
            ->freeze($mon_file)
        ;
    }

?>
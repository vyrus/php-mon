<?php
    
    require_once 'init.php';
    
    $mon_file = MONITORS . DS . 'default';
    
    //$action = 'create';
    //$action = 'update';
    
    if ('create' == $action)
    {
        Monitor::create()
            ->setLastConsolTime(time())
            ->setConsolidatePeriod(5 * Time::MINUTE)
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
<?php

    define('TIME_SECOND', 1);
    define('TIME_MINUTE', 60 * TIME_SECOND);
    define('TIME_HOUR',   60 * TIME_MINUTE);

    $measures = array
    (
            array
            (
                    array(20, 47),
                    array(20, 50),
                    array(20, 45)
            ),
            
            array(
                    array(20, 47),
                    array(20, 50),
                    array(20, 45)
            )
    );
    
    class Stats 
    {
            const VALUE_TYPE_AVG = 'average';
            
            protected $_num_steps = 3;
            
            protected $_slots = array();
            
            protected $_stats = array();
            
            protected $_last_stats_update = 0;
            
            protected $_last_slots_update = 0;
            
            protected $_stats_update_period;
            
            public function setUpdatePeriod($period) {
                $this->_stats_update_period = $period;
            }
            
            public function update($time, $value) {
                    $this->_slots[] = $value;
                    $this->_last_slots_update = $time;
                    
                    $diff = $this->_last_slots_update - $this->_last_stats_update;
                    
                    if ($diff >= $this->_stats_update_period)
                    {
                            $this->_consolidate();
                            $this->_last_stats_update = $this->_last_slots_update;
                    }
            }
            
            protected function _consolidate() {
                    $rec = new stdClass();
                    $rec->start_time = $this->_last_stats_update;
                    $rec->stop_time  = $this->_last_slots_update;
                    $rec->type = self::VALUE_TYPE_AVG;
                    $rec->value = array_sum($this->_slots) / sizeof($this->_slots);
                    
                    $this->_stats[] = $rec;
                    $this->_slots = array();
            }
            
            public function display() {
                    print_r($this->_stats);
            }
    }
    
    $time = 0;
    $stats = new Stats();
    $stats->setUpdatePeriod(60 * TIME_MINUTE);
    
    foreach ($measures as $hour => $temps)
    {
            foreach ($temps as $temp)
            {
                    $time += $temp[0] * TIME_MINUTE;
                    $stats->update($time, $temp[1]);
            }
    }
    
    header('Content-Type: text/plain; charset=utf-8');
    $stats->display();

?>
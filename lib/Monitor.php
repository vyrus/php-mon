<?php

    class Monitor extends Stats_Abstract {
        protected $_slots = array();
        
        protected $_records = array();
        
        protected $_last_consol_time = 0;
        
        protected $_last_slots_update = 0;
        
        protected $_consol_period;
        
        /**
        * Сколько консолидированных значений должен постоянно хранить монитор.
        * 
        * @var int
        */
        protected $_num_stored_values;
        
        public static function create() {
            return new self();
        }
        
        public function freeze($path) {
            $data = serialize($this);
            return file_put_contents($path, $data);
        }
        
        public static function thaw($path) {
            $data = file_get_contents($path);
            $monitor = unserialize($data);
            
            return $monitor;
        }
        
        public function setConsolidationPeriod($period) {
            $this->_consol_period = $period;
            return $this;
        }
        
        public function setLastConsolTime($time) {
            $this->_last_consol_time = $time;
            return $this; 
        }
        
        public function setNumStoredValues($num_values) {
            $this->_num_stored_values = $num_values;
            return $this;
        }
        
        public function update($time, $value) {
            $this->_slots[] = $value;
            $this->_last_slots_update = $time;
            
            $diff = $this->_last_slots_update - $this->_last_consol_time;
            
            if ($diff >= $this->_consol_period)
            {
                $this->_consolidate();
                $this->_last_consol_time = $this->_last_slots_update;
            }
            
            return $this;
        }
        
        protected function _consolidate() {
            $rec = Monitor_Record::create()
                ->start_time($this->_last_stats_update)
                ->stop_time($this->_last_slots_update)
                ->type(Monitor_Record::VALUE_TYPE_AVG)
                ->value(array_sum($this->_slots) / sizeof($this->_slots))
            ;
                
            $this->_records[] = $rec;
            $this->_slots = array();
        }
    }

?>
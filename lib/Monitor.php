<?php

    class Monitor extends Stats_Abstract {
        const ERROR_SUCCESS = 'error-success';
        
        const ERROR_STORAGE_INIT = 'error-storage-init';
        
        const ERROR_STORAGE_OPEN = 'error-open';
        
        const ERROR_STORAGE_CLOSE = 'error-close';
        
        const ERROR_LOAD_SETTINGS = 'error-load-settings';
        
        const ERROR_LOAD_INDICATORS = 'error-load-indicators';
        
        protected $_last_consol_time = 0;
        
        protected $_last_slots_update = 0;
        
        protected $_consol_period;
        
        /**
        * Сколько консолидированных значений должен постоянно хранить монитор.
        * 
        * @todo перенести в storage?
        * @var int
        */
        protected $_max_stored_values;
        
        /**
        * Хранилище данных монитора.
        * 
        * @var Monitor_Storage_Interface
        */
        protected $_storage;
        
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
        
        public function setLastConsolidationTime($time) {
            $this->_last_consol_time = $time;
            return $this; 
        }
        
        public function setLastSlotsUpdate($time) {
            $this->_last_slots_update = $time;
            return $this;
        }
        
        /**
        * Устанавливает, сколько значений монитор должен сохранять.
        * 
        * @param int $num_values
        * @return Monitor Fluent interface.
        */
        public function setMaxStoredValues($num_values) {
            $this->_max_stored_values = $num_values;
            return $this;
        }
        
        /**
        * Установка объекта-хранилища для монитора.
        * 
        * @param Monitor_Storage_Interface $storage
        * @return Monitor Fluent interface.
        */
        public function setStorage(Monitor_Storage_Interface $storage) {
            $this->_storage = $storage;
            return $this;
        }
        
        /**
        * Инициализация монитора.
        * 
        * @return mixed
        */
        public function init() {
            $this->initIndicator(0, array('max', 'min', 'average'));
            
            $settings = Class_MagicSetter::create()
                ->consol_period($this->_consol_period)
                ->max_stored_values($this->_max_stored_values)
                ->last_consol_time($this->_last_consol_time)
                ->last_slots_update($this->_last_slots_update)
                /**
                * @todo self::_getIndicators().
                */
                ->indicators($this->indicators)
            ;
            
            $success = Monitor_Storage_Abstract::ERROR_SUCCESS; 
            if ($success !== $this->_storage->init($settings)) {
                return self::ERROR_STORAGE_INIT;
            }
            
            return self::ERROR_SUCCESS;
        }
        
        public function open() {
            $success = Monitor_Storage_Abstract::ERROR_SUCCESS; 
            if ($success !== $this->_storage->open()) {
                return self::ERROR_STORAGE_OPEN;
            }
            
            $settings = null;
            if ($success !== $this->_storage->loadSettings($settings)) {
                return self::ERROR_LOAD_SETTINGS;
            }
            
            $this
                ->setConsolidationPeriod($settings->consol_period)
                ->setMaxStoredValues($settings->max_stored_values)
                ->setLastConsolidationTime($settings->last_consol_time)
                ->setLastSlotsUpdate($settings->last_slots_update)
            ;
            
            $result = $this->_storage->loadIndicators($this->indicators);
            if ($success !== $result) {
                return self::ERROR_LOAD_INDICATORS;
            }
            
            return self::ERROR_SUCCESS;
        }
        
        public function update($time, $value) {
            $this->addIndicatorValue(0, $value);
                
            $this->_last_slots_update = $time;
            $diff = $this->_last_slots_update - $this->_last_consol_time;
            
            if ($diff >= $this->_consol_period)
            {
                $value = $this->getIndicatorStats(0);
                $value['period'] = array($this->_last_consol_time,
                                         $this->_last_slots_update);
                
                $this->deleteIndicator(0);
                $this->initIndicator(0, array('max', 'min', 'average'));
            
                $this->_storage->addValue($value, $this->_max_stored_values);
                $this->_last_consol_time = $this->_last_slots_update;
            }
            
            return self::ERROR_SUCCESS;
        }
        
        public function close() {
            $settings = Class_MagicSetter::create()
                ->consol_period($this->_consol_period)
                ->max_stored_values($this->_max_stored_values)
                ->last_consol_time($this->_last_consol_time)
                ->last_slots_update($this->_last_slots_update)
                /**
                * @todo self::_getIndicators().
                */
                ->indicators($this->indicators)
            ;
            
            $success = Monitor_Storage_Abstract::ERROR_SUCCESS;
            if ($success !== $this->_storage->close($settings)) {
                return self::ERROR_STORAGE_CLOSE;
            }
            
            return self::ERROR_SUCCESS;
        }
        
        public function show() {
            return $this->_storage->loadValues();
        }
    }

?>
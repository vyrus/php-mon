<?php
    
    class Monitor_Storage_Array extends Monitor_Storage_Abstract {
        const ERROR_CREATE_FILE = 'error-cannot-create-file';
        
        const ERROR_OPEN = 'error-open';
        
        const ERROR_READ = 'error-read';
        
        const ERROR_WRITE = 'error-write';
        
        const ERROR_UNSERIALIZE = 'error-unserialize';
        
        protected $_file;
        
        protected $_storage;
        
        public static function create() {
            return new self();
        }
        
        /**
        * Устанавливает, в какой файле сохранять базу.
        * 
        * @param mixed $file
        */
        public function set_file($file) {
            $this->_file = $file;
            return $this;
        }
        
        public function init(stdClass $s) {
            $storage = array(
                'settings' => array(
                    'max_stored_values' => $s->max_stored_values,
                    'consol_period'     => $s->consol_period,
                    'last_consol_time'  => $s->last_consol_time,
                    'last_indicators_update' => $s->last_indicators_update
                ),
                'indicators' => $s->indicators,
                'values' => array()
            );
            
            $data = serialize($storage);
            if (!file_put_contents($this->_file, $data)) {
                return self::ERROR_CREATE_FILE;
            }
            
            return self::ERROR_SUCCESS;
        }
        
        public function open() {
            $data = file_get_contents($this->_file);
            
            if (!$data) {
                return self::ERROR_READ;
            }
            
            if (false === ($this->_storage = unserialize($data))) {
                return self::ERROR_UNSERIALIZE;
            }
            
            return self::ERROR_SUCCESS;
        }
        
        public function load_settings(& $settings) {
            $s = & $this->_storage['settings'];
            
            $settings = Class_MagicSetter::create()
                ->max_stored_values($s['max_stored_values'])
                ->consol_period($s['consol_period'])
                ->last_consol_time($s['last_consol_time'])
                ->last_indicators_update($s['last_indicators_update'])
            ;
            
            return self::ERROR_SUCCESS;
        }
        
        public function load_indicators(& $indicators) {
            $indicators = $this->_storage['indicators'];
            
            return self::ERROR_SUCCESS;
        }
        
        public function add_value($value, $max_values) {
            $values = & $this->_storage['values'];
            
            if ($max_values == sizeof($values)) {
                array_shift($values);
            }
            
            $values[] = $value;
        }
        
        public function load_values() {
            return $this->_storage['values'];
        }
        
        public function close(stdClass $p) {
            $s = & $this->_storage['settings'];
            
            $s['last_consol_time'] = $p->max_stored_values;
            $s['last_indicators_update'] = $p->last_indicators_update;
            $this->_storage['indicators'] = $p->indicators;
            
            $data = serialize($this->_storage);
            
            if (!file_put_contents($this->_file, $data)) {
                return self::ERROR_WRITE;
            }
            
            return self::ERROR_SUCCESS;
        }
    }

?>
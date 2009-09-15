<?php
    
    /**
    * Хранилище состояния мониторов в виде кольцевой БД (round-robin database).
    */
    class Monitor_Storage_Rrd extends Monitor_Storage_Abstract {
        const ERROR_CREATE_FILE = 'error-cannot-create-file';
        
        const ERROR_OPEN = 'error-open';
        
        const ERROR_READ = 'error-read';
        
        const ERROR_WRITE = 'error-write';
        
        const ERROR_CLOSE = 'error-close';
        
        /**
        * Путь до файла с базой.
        * 
        * @var string
        */
        protected $_file;
        
        protected $_fp;
        
        protected $_num_slots_used;
        
        public static function create() {
            return new self();
        }
        
        /**
        * Устанавливает, в какой файле сохранять базу.
        * 
        * @param mixed $file
        */
        public function setFile($file) {
            $this->_file = $file;
            return $this;
        }
        
        public function init(stdClass $settings) {
            if (false === ($this->_fp = fopen($this->_file, 'w'))) {
                return self::ERROR_CREATE_FILE;
            }
            
            $data  = $this->_packULong($settings->max_stored_values);
            $data .= $this->_packULong($settings->consol_period);
            $data .= $this->_packULong($settings->last_consol_time);
            $data .= $this->_packULong($settings->last_slots_update);
            
            $num_slots_used = 0;
            $data .= $this->_packULong($num_slots_used);
            
            $cur_cell_pointer = 0;
            $data .= $this->_packULong($cur_cell_pointer);
            
            $zero = $this->_packULong(0);
            $data .= str_repeat($zero, $settings->max_stored_values);
            
            if (false === $this->_write($data)) {
                return self::ERROR_WRITE;
            }
            
            fclose($this->_fp);
            
            return self::ERROR_SUCCESS;
        }
        
        public function open() {
            if (false === ($this->_fp = fopen($this->_file, 'r+'))) {
                return self::ERROR_OPEN;
            }
            
            return self::ERROR_SUCCESS;
        }
        
        public function load(& $settings) {
            if (false === ($data = $this->_read(4 * 4))) {
                return self::ERROR_READ;
            }
            
            $cs = $this->_unpackULong(substr($data, 0, 4));
            $ms = $this->_unpackULong(substr($data, 4, 4));
            $lc = $this->_unpackULong(substr($data, 8, 4));
            $ls = $this->_unpackULong(substr($data, 12, 4));
            $su = $this->_unpackULong(substr($data, 16, 4));
            
            $settings = Class_MagicSetter::create()
                ->consol_period($cs)
                ->max_stored_values($ms)
                ->last_consol_time($lc)
                ->last_slots_update($ls)
            ;
            
            $this->_num_slots_used = $su;
            
            return self::ERROR_SUCCESS;
        }
        
        public function loadSlots() {
            //
        }
        
        protected function _packULong($long) {
            return pack('N', $long);
        }
        
        protected function _unpackULong($ulong) {
            return current(unpack('N', $ulong));
        }
        
        protected function _read($num_bytes) {
            return fread($this->_fp, $num_bytes);
        }
        
        protected function _write($data) {
            if (false === ($bytes_written = fwrite($this->_fp, $data))) {
                return false;
            }
            
            $len = strlen($data);
            return $bytes_written === $len;
        }
    }

?>
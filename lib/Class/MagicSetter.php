<?php

    class Class_MagicSetter extends stdClass {
        public static function create() {
            return new self();
        }
        
        protected function _setAttribute($name, $value) {
            $this->$name = $value;
            return $this;
        }
        
        public function __call($name, $args) {
            return $this->_setAttribute($name, array_shift($args));
        }
    }

?>
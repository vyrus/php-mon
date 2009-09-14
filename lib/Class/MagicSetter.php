<?php

    class Class_MagicSetter {
        protected function _setAttribute($name, $value) {
            $this->$name = $value;
            return $this;
        }
        
        public function __call($name, $args) {
            return $this->_setAttribute($name, array_shift($args));
        }
    }

?>
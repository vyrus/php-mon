<?php

    class Monitor_Record extends Class_MagicSetter {
        const VALUE_TYPE_AVG = 'average';
        
        public static function create() {
            return new self();
        }
    }

?>
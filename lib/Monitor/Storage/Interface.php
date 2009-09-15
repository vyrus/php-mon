<?php

    interface Monitor_Storage_Interface {
        /**
        * Инициализация пустого хранилища.
        * 
        * @param stdClass $settings
        */
        public function init(stdClass $settings);
    }

?>
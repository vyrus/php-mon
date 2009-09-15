<?php

    /* $Id: Abstract.php 60 2009-05-28 09:52:38Z Vyrus $ */
    
    /**
    * Класс с методами для подсчёта статистики индикаторов (показателей).
    */
    abstract class Stats_Abstract {
        /**
        * Максимальное значение индикатора.
        * 
        * @var const
        */
        const OPT_MAX = 'max';
        
        /**
        * Минимальное значение индикатора.
        * 
        * @var const
        */
        const OPT_MIN = 'min';
        
        /**
        * Среднее (взвешенное, если передавались весовые коэффициенты) значение
        * индикатора.
        * 
        * @var const
        */
        const OPT_AVG = 'avg';
        
        /**
        * Стандартное (среднеквадратичное) отклонение значений индикатора.
        * 
        * @var const
        */
        const OPT_STD_DEV = 'std-dev';
        
        /**
        * История значений индикатора.
        * 
        * @var const
        */
        const OPT_HISTORY = 'history';
        
        /**
        * Массив счётчиков.
        * 
        * @var array
        */
        protected $_counters = array();
        
        /**
        * Массив показателей.
        * 
        * @var array
        */
        protected $_indicators = array();
        
        /**
        * Увеличивает значение счётчика.
        * 
        * @param  int|string $name  Название счётчика.
        * @param  int        $value На сколько увеличить значение счётчика.
        * @return void
        */
        protected function inc_counter($name, $value = 1) {
            /* Если такого счётчика ещё нет, то создаём его */
            if (!array_key_exists($name, $this->_counters)) {
                $this->_counters[$name] = 0;
            }
            
            $this->_counters[$name] += $value;
        }
        
        /**
        * Получение значения счётчика.
        * 
        * @param  int|string $name Название счётчика.
        * @return int Текущее значение счётчика.
        */
        protected function get_counter($name) {
            /* Если такого счётчика ещё нет, то создаём его */
            if (!array_key_exists($name, $this->_counters)) {
                $this->_counters[$name] = 0;
            }
            
            return $this->_counters[$name];
        }
        
        /**
        * Создаёт новый индикатор с подсчётом указанных для него параметров.
        * 
        * @param  int|string $name    Название индикатора.
        * @param  array      $options Опции инликатора (параметры статистики).
        * @return void
        * @throws InvalidArgumentException Если индикатор  уже существует. 
        */
        protected function create_indicator($name, array $options) {
            if (array_key_exists($name, $this->_indicators)) {
                $msg = 'Индикатор с именем "' . $name .'" уже создан';
                throw new InvalidArgumentException($msg);
            }
            
            $i = & $this->_indicators[$name];
            $i = new stdClass();
            $i->options = $options;
            
            foreach ($options as $option)
            {
                switch ($option) {
                    case self::OPT_MAX:
                        $i->max = null;
                        break;
                        
                    case self::OPT_MIN:
                        $i->min = null;
                        break;
                        
                    case self::OPT_AVG:
                        $i->sum_weighted_values = 0;
                        $i->sum_weights = 0;
                        $i->num_values = 0; 
                        break;
                    
                    case self::OPT_STD_DEV:
                        $i->sum_values  = 0;
                        $i->sum_squares = 0;
                        $i->num_values  = 0;
                        break;
                        
                    case self::OPT_HISTORY:
                        $i->history = array();
                        break;
                        
                    default:
                        $msg = 'Неизвестная опция "' . $option . '"';
                        throw new InvalidArgumentException($msg);
                        break;
                }
            }
        }
        
        /**
        * Удаление индикатора.
        * 
        * @param  int|string $name Название индикатора
        * @return void
        */
        public function delete_indicator($name) {
            $this->_check_indicator_existence($name);
            unset($this->_indicators[$name]);
        }
        
        /**
        * Обрабатывает очередное значение индикатора.
        * 
        * @param  int|string $name   Название индикатора.
        * @param  int        $value  Значение индикатора.
        * @param  int        $weight Весовой коэффициент значения.
        * @param  int        $time   Время появления значения.
        * @return void
        */
        protected function update_indicator($name, $value, $weight = 1,
                                                           $time = null) {
            $this->_check_indicator_existence($name);
            
            $i = & $this->_indicators[$name];
            
            $average       = in_array(self::OPT_AVG,     $i->options);
            $std_deviation = in_array(self::OPT_STD_DEV, $i->options);
            
            if ($average || $std_deviation) {
                $i->num_values += 1;
            }
            
            foreach ($i->options as $option)
            {
                switch ($option)
                {
                     case self::OPT_MAX:
                        if ($value > $i->max || null === $i->max) {
                            $i->max = $value;
                        }
                        break;
                        
                    case self::OPT_MIN:
                        if ($value < $i->min || null === $i->min) {
                            $i->min = $value;
                        }
                        break;
                        
                    case self::OPT_AVG:
                        $i->sum_weighted_values += $weight * $value;
                        $i->sum_weights += $weight; 
                        break;
                    
                    case self::OPT_STD_DEV:
                        $i->sum_values  += $value;
                        $i->sum_squares += pow($value, 2);
                        break;
                        
                    case self::OPT_HISTORY:
                        if (null !== $time) {
                            $i->history[$time] = $value;
                        }
                        break;
                }
            }
        }
        
        /**
        * Получение значений статистических параметров индикатора, заданных при
        * его создании.
        * 
        * @param  int|string $name Название индикатора.
        * @return array Статистика значений индикатора.
        */
        protected function get_indicator($name) {
            $this->_check_indicator_existence($name);
            
            $i = & $this->_indicators[$name];
            $stats = array();
            
            foreach ($i->options as $option)
            {
                switch ($option)
                {
                    case self::OPT_MAX:
                        $stats['max'] = $i->max;
                        break;
                        
                    case self::OPT_MIN:
                        $stats['min'] = $i->min;
                        break;
                        
                    case self::OPT_AVG:
                        $stats['avg'] = (
                            $i->num_values > 0
                                ? $i->sum_weighted_values / $i->sum_weights
                                : null
                        ); 
                        break;
                    
                    case self::OPT_STD_DEV:
                        /**
                        * @todo А правильно ли будет считаться отклонение, если вес != 1?
                        */
                        $num_1 = (
                            $i->num_values > 0
                                ? $i->sum_squares / $i->num_values
                                : 0
                        );
                        $num_2 = (
                            $i->num_values > 0
                                ? $i->sum_values / $i->num_values
                                : 0
                        );
                        
                        $num_2 = pow($num_2, 2);
                        $stats['std_dev'] = sqrt($num_1 - $num_2);
                        break; 
                        
                    case self::OPT_HISTORY:
                        $stats['history'] = $i->history;
                        break;
                }
            }
            
            return $stats;
        }
        
        /**
        * Проверка существования индикатора. Если указанный индикатор не
        * существует, то генерируется исключение.
        * 
        * @param  int|string $name
        * @return void
        * @throws InvalidArgumentException
        */
        protected function _check_indicator_existence($name) {
            if (!array_key_exists($name, $this->_indicators))
            {
                $msg = 'Индикатор с именем "' . $name .'" не найден';
                throw new InvalidArgumentException($msg);
            }
        }
        
        /**
        * Возврашает массив со служебными данными индикаторов.
        * 
        * @return array
        */
        protected function _get_indicators_array() {
            return $this->_indicators;
        }
        
        /**
        * Устанавливает значения массива индикаторов.
        * 
        * @param array $i Массив индикаторов.
        */
        protected function _set_indicators_array(array $i) {
            $this->_indicators = $i;
        }
    }

?>
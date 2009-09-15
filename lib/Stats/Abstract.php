<?php

    /* $Id: Abstract.php 60 2009-05-28 09:52:38Z Vyrus $ */
    
    /**
    * Класс с общими функциями для сбора статистики.
    */
    abstract class Stats_Abstract {
        const OPT_MAX = 'max';
        
        const OPT_MIN = 'min';
        
        const OPT_AVG = 'avg';
        
        const OPT_STD_DEV = 'std-dev';
        
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
        * @param  string $name  Название счётчика.
        * @param  int    $value На сколько единиц увеличить значение счётчика.
        * @return void
        */
        protected function incCounter($name, $value = 1) {
            /* Если такого счётчика ещё нет, то создаём его */
            if (!array_key_exists($name, $this->_counters)) {
                $this->_counters[$name] = 0;
            }
            
            $this->_counters[$name] += $value;
        }
        
        /**
        * Получение значения счётчика.
        * 
        * @param  string $name Название счётчика.
        * @return int Текущее значение счётчика.
        */
        protected function getCounter($name) {
            /* Если такого счётчика ещё нет, то создаём его */
            if (!array_key_exists($name, $this->_counters)) {
                $this->_counters[$name] = 0;
            }
            
            return $this->_counters[$name];
        }
        
        /**
        * //
        * 
        * @todo rename to createIndicator?
        * 
        * @param  int|string $name
        * @param  array      $options
        * @return void
        */
        protected function initIndicator($name, array $options) {
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
        * @param  int|string $name
        * @return void
        */
        public function deleteIndicator($name) {
            $this->checkIndicatorExistence($name);
            unset($this->_indicators[$name]);
        }
        
        /**
        * Возврашает массив со служебными данными индикаторов.
        */
        protected function _get_indicators_array() {
            return $this->_indicators;
        }
        
        /**
        * Проверка существования индикатора. Если указанный индикатор не
        * существует, то генерируется исключение.
        * 
        * @param  int|string $name
        * @return void
        * @throws InvalidArgumentException
        */
        protected function checkIndicatorExistence($name) {
            if (!array_key_exists($name, $this->_indicators))
            {
                $msg = 'Индикатор с именем "' . $name .'" не найден';
                throw new InvalidArgumentException($msg);
            }
        } 
        
        /**
        * Обрабатывает очередное значение показателя: находит минимальное и
        * максимальное значения из всех переданных значений и их сумму.
        * 
        * @param  string $name   Название показателя.
        * @param  int    $value  Значение показателя.
        * @param  int    $weight Весовой коэффициент значения.
        * @param  int    $time   Время появления значения показателя.
        * @return void
        */
        protected function addIndicatorValue($name, $value, $weight = 1, $time = null) {
            $this->checkIndicatorExistence($name);
            
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
        * Получение статистики по значениям показателя: минимум, максимум,
        * сумму всех значений, кол-во учтённых значений (или общую сумму весов)
        * и среднее значение показателя.
        * 
        * @param  string $name Название показателя.
        * @return array Статистика значений показателя.
        */
        protected function getIndicatorStats($name) {
            $this->checkIndicatorExistence($name);
            
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
                        $stats['average'] = (
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
                        $stats['std_deviation'] = sqrt($num_1 - $num_2);
                        break; 
                        
                    case self::OPT_HISTORY:
                        $stats['history'] = $i->history;
                        break;
                }
            }
            
            return $stats;
        }
    }

?>
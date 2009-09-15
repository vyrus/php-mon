<?php

    /* $Id: Abstract.php 60 2009-05-28 09:52:38Z Vyrus $ */
    
    /**
    * Класс с общими функциями для сбора статистики.
    */
    abstract class Stats_Abstract {
        /**
        * @todo Константы с опциями индикаторов.
        */
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
                throw new InvalidArgumentException('Индикатор с именем "' . $name .'" уже создан');
            }
            
            $i = & $this->_indicators[$name];
            $i = array('options' => $options);
            
            foreach ($options as $option)
            {
                switch ($option) {
                    case self::OPT_MAX:
                        $i['max'] = null;
                        break;
                        
                    case self::OPT_MIN:
                        $i['min'] = null;
                        break;
                        
                    case self::OPT_AVG:
                        $i['sum_weighted_values'] = 0;
                        $i['sum_weights'] = 0;
                        $i['num_values']  = 0; 
                        break;
                    
                    case self::OPT_STD_DEV:
                        $i['sum_values']  = 0;
                        $i['sum_squares'] = 0;
                        $i['num_values']  = 0;
                        break;
                        
                    case self::OPT_HISTORY:
                        $i['history'] = array();
                        break;
                        
                    default:
                        throw new InvalidArgumentException('Неизвестная опция "' . $option . '"');
                        break;
                }
            }
        }
        
        /**
        * @todo man it.
        */
        public function deleteIndicator($name) {
            $this->checkIndicatorExistence($name);
            unset($this->_indicators[$name]);
        }
        
        protected function _get_indicators_array() {
            return $this->_indicators;
        }
        
        /**
        * //
        * 
        * @param  int|string $name
        * @return void
        * @throws InvalidArgumentException
        */
        protected function checkIndicatorExistence($name) {
            if (!array_key_exists($name, $this->_indicators)) {
                throw new InvalidArgumentException('Индикатор с именем "' . $name .'" не найден');
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
            
            $indicator = & $this->_indicators[$name];
            
            $average       = in_array(self::OPT_AVG,     $indicator['options']);
            $std_deviation = in_array(self::OPT_STD_DEV, $indicator['options']);
            
            if ($average || $std_deviation) {
                $indicator['num_values'] += 1;
            }
            
            foreach ($indicator['options'] as $option)
            {
                switch ($option) {
                     case self::OPT_MAX:
                        if ($value > $indicator['max'] || null === $indicator['max']) {
                            $indicator['max'] = $value;
                        }
                        break;
                        
                    case self::OPT_MIN:
                        if ($value < $indicator['min'] || null === $indicator['min']) {
                            $indicator['min'] = $value;
                        }
                        break;
                        
                    case self::OPT_AVG:
                        $indicator['sum_weighted_values'] += $weight * $value;
                        $indicator['sum_weights'] += $weight; 
                        break;
                    
                    case self::OPT_STD_DEV:
                        $indicator['sum_values']  += $value;
                        $indicator['sum_squares'] += pow($value, 2);
                        break;
                        
                    case self::OPT_HISTORY:
                        if (null !== $time) {
                            $indicator['history'][$time] = $value;
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
            
            $indicator = & $this->_indicators[$name];
            $stats = array();
            
            foreach ($indicator['options'] as $option)
            {
                switch ($option) {
                    case self::OPT_MAX:
                        $stats['max'] = $indicator['max'];
                        break;
                        
                    case self::OPT_MIN:
                        $stats['min'] = $indicator['min'];
                        break;
                        
                    /**
                    * @todo return null if no values were provided.
                    */
                    case self::OPT_AVG:
                        $stats['average'] = (
                            $indicator['num_values'] > 0
                                ? $indicator['sum_weighted_values'] / $indicator['sum_weights']
                                : 0
                        ); 
                        break;
                    
                    case self::OPT_STD_DEV:
                        /**
                        * @todo А правильно ли будет считаться отклонение, если вес != 1?
                        */
                        $num_1 = (
                            $indicator['num_values'] > 0
                                ? $indicator['sum_squares'] / $indicator['num_values']
                                : 0
                        );
                        $num_2 = (
                            $indicator['num_values'] > 0
                                ? $indicator['sum_values'] / $indicator['num_values']
                                : 0
                        );
                        $num_2 = pow($num_2, 2);
                        $stats['std_deviation'] = sqrt($num_1 - $num_2);
                        break; 
                        
                    case self::OPT_HISTORY:
                        $stats['history'] = $indicator['history'];
                        break;
                }
            }
            
            return $stats;
        }
    }

?>
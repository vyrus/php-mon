<?php

    /* $Id: Abstract.php 60 2009-05-28 09:52:38Z Vyrus $ */
    
    /**
    * Класс с общими функциями для сбора статистики.
    */
    abstract class Stats_Abstract {
        /**
        * Массив счётчиков.
        * 
        * @var array
        */
        protected $counters = array();
        
        /**
        * Массив показателей.
        * 
        * @var array
        */
        protected $indicators = array();
        
        /**
        * Увеличивает значение счётчика.
        * 
        * @param  string $name  Название счётчика.
        * @param  int    $value На сколько единиц увеличить значение счётчика.
        * @return void
        */
        protected function incCounter($name, $value = 1) {
            /* Если такого счётчика ещё нет, то создаём его */
            if (!array_key_exists($name, $this->counters)) {
                $this->counters[$name] = 0;
            }
            
            $this->counters[$name] += $value;
        }
        
        /**
        * Получение значения счётчика.
        * 
        * @param  string $name Название счётчика.
        * @return int Текущее значение счётчика.
        */
        protected function getCounter($name) {
            /* Если такого счётчика ещё нет, то создаём его */
            if (!array_key_exists($name, $this->counters)) {
                $this->counters[$name] = 0;
            }
            
            return $this->counters[$name];
        }
        
        /**
        * //
        * 
        * @param  int|string $name
        * @param  array      $options
        * @return void
        */
        protected function initIndicator($name, array $options) {
            if (array_key_exists($name, $this->indicators)) {
                throw new InvalidArgumentException('Индикатор с именем "' . $name .'" уже создан');
            }
            
            $indicator = & $this->indicators[$name];
            $indicator = array('options' => $options);
            
            foreach ($options as $option)
            {
                switch ($option) {
                    case 'max':
                        $indicator['max'] = null;
                        break;
                        
                    case 'min':
                        $indicator['min'] = null;
                        break;
                        
                    case 'average':
                        $indicator['sum_weighted_values'] = 0;
                        $indicator['sum_weights'] = 0;
                        $indicator['num_values']  = 0; 
                        break;
                    
                    case 'std_deviation':
                        $indicator['sum_values']  = 0;
                        $indicator['sum_squares'] = 0;
                        $indicator['num_values']  = 0;
                        break;
                        
                    case 'history':
                        $indicator['history'] = array();
                        break;
                        
                    default:
                        throw new InvalidArgumentException('Неизвестная опция "' . $option . '"');
                        break;
                }
            }
        }
        
        /**
        * //
        * 
        * @param  int|string $name
        * @return void
        * @throws InvalidArgumentException
        */
        protected function checkIndicatorExistence($name) {
            if (!array_key_exists($name, $this->indicators)) {
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
            
            $indicator = & $this->indicators[$name];
            
            $average       = in_array('average',       $indicator['options']);
            $std_deviation = in_array('std_deviation', $indicator['options']);
            
            if ($average || $std_deviation) {
                $indicator['num_values'] += 1;
            }
            
            foreach ($indicator['options'] as $option)
            {
                switch ($option) {
                     case 'max':
                        if ($value > $indicator['max'] || null === $indicator['max']) {
                            $indicator['max'] = $value;
                        }
                        break;
                        
                    case 'min':
                        if ($value < $indicator['min'] || null === $indicator['min']) {
                            $indicator['min'] = $value;
                        }
                        break;
                        
                    case 'average':
                        $indicator['sum_weighted_values'] += $weight * $value;
                        $indicator['sum_weights'] += $weight; 
                        break;
                    
                    case 'std_deviation':
                        $indicator['sum_values']  += $value;
                        $indicator['sum_squares'] += pow($value, 2);
                        break;
                        
                    case 'history':
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
            
            $indicator = & $this->indicators[$name];
            $stats = array();
            
            foreach ($indicator['options'] as $option)
            {
                switch ($option) {
                    case 'max':
                        $stats['max'] = $indicator['max'];
                        break;
                        
                    case 'min':
                        $stats['min'] = $indicator['min'];
                        break;
                        
                    case 'average':
                        $stats['average'] = (
                            $indicator['num_values'] > 0
                                ? $indicator['sum_weighted_values'] / $indicator['sum_weights']
                                : 0
                        ); 
                        break;
                    
                    case 'std_deviation':
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
                        
                    case 'history':
                        $stats['history'] = $indicator['history'];
                        break;
                }
            }
            
            return $stats;
        }
    }

?>
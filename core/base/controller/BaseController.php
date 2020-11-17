<?php
namespace core\base\controller;


use core\base\exceptions\RouteException;

/**
 * Class BaseController
 *
 * @package core\base\controller
 */
abstract class BaseController
{

    /**
     * @var string $page
     * @var array $errors
     */
    protected $page;
    protected $errors;


    /**
     * @var string $controller [ controller name ]
     * @var string $inputMethod [ action name ]
     * @var string $outputMethod [ for view ]
     * @var array $parameters [ params from url ]
     */
    protected $controller;
    protected $inputMethod;
    protected $outputMethod;
    protected $parameters;


    /**
     * @throws \ReflectionException
     * @throws RouteException
     */
    public function route()
    {
        // получаем имя контроллер
        $controller = str_replace('/', '\\', $this->controller);


        try {
            // Reflection Method
            $object = new \ReflectionMethod($controller, 'request');

            // массив аргументов
            $args = [
                'parameters' => $this->parameters,
                'inputMethod' => $this->inputMethod,
                'outputMethod' => $this->outputMethod
            ];


            // call method request
            // [ Мы создаем объект класса и передаем его метод invoke
            $object->invoke(new $controller, $args);


        } catch (\ReflectionException $e) {

            throw new RouteException($e->getMessage());
        }

    }

    public function request($args)
    {

        $this->parameters = $args['parameters'];

        $inputData  = $args['inputMethod'];
        $outputData = $args['outputMethod'];


        // $this->{$inputMethod}();
        // call_user_func([$this, $inputData]);
        $this->$inputData();


        // Сохраняем результат обработки выполнения выходного метода в переменную $page
        // это будет результат $page
        // $this->page = $this->{$outputMethod}();
        // call_user_func([$this, $outputData]);
        $this->page = $this->$outputData();

        // если в ошибке что то есть
        if($this->errors)
        {
            $this->writeLog();
        }

        // Get Page [ Пулучаем страничку
        $this->getPage();
    }

    protected function render($path = '', $parameters = [])
    {
        extract($parameters);

        if(!$path)
        {
            $reflectedClass = new \ReflectionClass($this);
            $path = TEMPLATE . explode('controller', strtolower($reflectedClass->getShortName()))[0];
        }

        // Открываем буфер обмена
        ob_start();

        if(!@include_once $path . '.php')
        {
            throw new RouteException('Отсутствует шаблон - ' . $path);
        }

        // получаем из буфер то что там хранился
        return ob_get_clean();

    }


    // Завершаем выполнения скрипта и при этом показываем страницу
    protected function getPage() // terminate
    {
        exit($this->page);
    }
}
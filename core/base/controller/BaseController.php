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
        
    }
}
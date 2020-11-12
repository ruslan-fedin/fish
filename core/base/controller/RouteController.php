<?php
namespace core\base\controller;


use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use core\base\settings\ShopSettings;
use \Exception;


/**
 * Class RouteController
 *
 * Это мост весь нашего проекта
 * Он будет отвечать за все
 * Он должен иметь разбераться на такой адрес
 * http://shop.loc/catalog/phone/page/2
 *
 * @package core\base\controller
 */
class RouteController extends BaseController
{

    /**
     * @var RouteController $_instance
     */
    static private $_instance;

    /**
     * @var array $routes        [ all routes ]
     */
    protected $routes;



    /**
     * Запришение создания копию объекта
     *
     * prevent to clone object
     */
    private function __clone() {}


    /**
     * Get instance
     *
     *
     * @return self
     * @throws Exception
     */
    static public function getInstance()
    {
        if(self::$_instance instanceof self)
        {
            return self::$_instance;
        }

        return self::$_instance = new self;
    }


    /**
     * RouteController constructor.
     *
     * URL: http://shop.loc/catalog/phone
     * URI: /catalog/phone
     *
     * USER URL : http://shop.loc/catalog/phone
     * ADMIN URL: http://shop.loc/admin/shop/catalog/phone
     * [ admin: directory of admin, shop: name of plugin..]
     * @return void
     * @throws Exception
     *
     */
    private function __construct()
    {

        $adress_str = $_SERVER['REQUEST_URI']; // debug($adress_str);

        /**
         * если симболь '/' стоит в конце строки
         * и это не корень сайта
         *
         * мы должны перенаправить пользователь на страницу без этого симбола
         */
        // strrpos() ищем последние хождения по строки
        if(strrpos($adress_str, '/') === strlen($adress_str) -1 && strrpos($adress_str, '/') !== 0)
        {
            // мы должны перенаправить пользователь на страницу без этого симбола
            $this->redirect(rtrim($adress_str, '/'), 301); // 301 response code
        }

        // в переменная $path сохраним обрезаны строку в которой содержан имя выпольнения скрипта
        $path = substr($_SERVER['PHP_SELF'], 0, strpos($_SERVER['PHP_SELF'], 'index.php'));


        // если $path совпадает с нашем определен констант PATH то будем запускать преложение
        // в противным случае будет бросать ползователь
        if($path === PATH)
        {

            $this->routes = Settings::get('routes');

            if(!$this->routes)
            {
                throw new RouteException('Сайт находится на техническом обслуживании');
            }


            // USER [ http://shop.loc/catalog/phone
            $url = explode('/', substr($adress_str, strlen(PATH)));


            /**
             * USER URL : http://shop.loc/catalog/phone
             * ADMIN URL: http://shop.loc/admin/shop/catalog/phone [ admin: directory of admin, shop: name of plugin..]
             */

            /**
             * если у нас позиция alias равна строк
             * если это административный панель значит нам надо разбевать
             * строк относительно админ панель
             */
            // strpos() ищем первое ождения по строки
            if($url[0] && $url[0] === $this->routes['admin']['alias'])
            {

                // удалить первый элемент с массива
                array_shift($url);


                // full path to plugins
                $plugin_path = $_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0];

                if($url[0] && is_dir($plugin_path)) // если plugin
                {
                    // вытаскиваем перевый элемент с массива
                    $plugin = array_shift($url);

                    // получить настроеки плагина ]
                    $pluginSettings = $this->routes['settings']['path'] . ucfirst($plugin . 'Settings');

                    if(file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . $pluginSettings . '.php'))
                    {
                        $pluginSettings = str_replace('/', '\\', $pluginSettings);
                        $this->routes = $pluginSettings::get('routes');
                    }

                    $dir = $this->routes['plugins']['dir'] ? '/' . $this->routes['plugins']['dir'] . '/' : '/';
                    $dir = str_replace('//', '/', $dir);

                    $this->controller = $this->routes['plugins']['path'] . $plugin . $dir;

                    $hrUrl = $this->routes['plugins']['hrUrl'];

                    $route = 'plugins';

                }else{ // если не plugin

                    $this->controller = $this->routes['admin']['path'];

                    $hrUrl = $this->routes['admin']['hrUrl'];

                    $route = 'admin';
                }

            }else{


                $hrUrl = $this->routes['user']['hrUrl']; // апределяет исползовать ЧПУ или нет

                $this->controller = $this->routes['user']['path'];

                $route = 'user';
            }

            // Создание маршрутов
            $this->createRoute($route, $url);


            // Создание набор параметров адресные строки
            // shop.loc/news/title-of-news-1/color/red/id/4

            if($url[1])
            {
                $count = count($url);
                $key = '';

                // если работаем без ЧПУ
                if(!$hrUrl)
                {
                    $i = 1;

                }else{
                    $this->parameters['alias'] = $url[1];
                    $i = 2;
                }

                /**
                 * for($i = 0; $i < count($count); $i++) { }
                 * $i будет доступно для следущего записа
                 * при таком записе for(; $i < $count; $i++) { }
                 */
                for( ; $i < $count; $i++)
                {
                    if(!$key)
                    {
                        $key = $url[$i];
                        $this->parameters[$key] = '';

                    }else{

                        $this->parameters[$key] = $url[$i];
                        $key = '';
                    }
                }
            }


        }else{

            try
            {
                throw new Exception('Не корректная дирректория сайта');

            }catch (\Exception $e){

                exit($e->getMessage());
            }
        }

    }


    /**
     * Create Route
     *
     * @param $var [ admin, plugins, user ...]
     * @param $arr
     */
    private function createRoute($var, $arr)
    {
        $route = [];

        // контроллер
        if(!empty($arr[0]))
        {
            if($this->routes[$var]['routes'][$arr[0]])
            {
                $route = explode('/', $this->routes[$var]['routes'][$arr[0]]);

                $this->controller .= ucfirst($route[0]. 'Controller');

            }else{

                $this->controller .= ucfirst($arr[0]. 'Controller');
            }

        }else{

            $this->controller .= $this->routes['default']['controller'];
        }

        // получить методы
        $this->inputMethod  = $route[1] ? $route[1] : $this->routes['default']['inputMethod'];
        $this->outputMethod = $route[2] ? $route[2] : $this->routes['default']['outputMethod'];

        return;
    }

}
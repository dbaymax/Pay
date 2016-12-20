<?php
/**
 * Created by PhpStorm.
 * User: hmy
 * Date: 16-12-9
 * Time: 下午3:05
 */
namespace Common\AOP\AOP;

/**
 * 目标类可以暴露切面,使用者可以在切面绑定多个事件
 * Class AOP
 * @package Flash\Event
 */
class AOP{

    private static $_events = [];
    private static $_events_singleton = [];
    private static $_reflections = [];


    /**
     * 给类注册事件
     */
    public static function register($name,$handler,$class=null,$data=null,$append=false)
    {
        if(!is_callable($handler,true)){
            throw new  \Exception('The handler '.$handler[1].' is not callable, at '.get_class(new static()).'::'.'register');
        }
        //全局事件, 一个切面可绑定多个事件,按顺序执行
        if(!$class){
            $events = &self::$_events[$name];
        }
        else{
            //局部事件
            if(is_object($class)){
                $hash = spl_object_hash($class);
            }
            else{
                $hash = ltrim($class,'\\');
            }
            $events = &self::$_events[$name][$hash];
        }

        if($append || empty($events)){
            $events[] = [$handler,$data];
        }
        else{
            array_unshift($events,[$handler,$data]);
        }

    }

    /**
     * 只能注册一个事件
     */
    public function registerSingleton($name,$handler,$class=null,$data=null)
    {
        if(!is_callable($handler,true)){
            throw new  \Exception('The handler '.$handler[1].' is not callable, at '.get_class(new static()).'::'.'register');
        }
        //全局事件, 一个切面可绑定多个事件,按顺序执行
        if(!$class){
            self::$_events_singleton[$name] = [$handler,$data];
        }
        else{
            //局部事件
            if(is_object($class)){
                $hash = spl_object_hash($class);
            }
            else{
                $hash = ltrim($class,'\\');
            }
            self::$_events[$name][$hash] = [$handler,$data];
        }

    }


    public static function remove($name,$handler=null,$class=null)
    {
        if(!$class){
            //全局事件
            $events = &self::$_events[$name];
        }
        else{
            //局部事件
            if(is_object($class)){
                $hash = spl_object_hash($class);
            }
            else{
                $hash = ltrim($class,'\\');
            }
            $events = &self::$_events[$name][$hash];
        }

        if(empty($events)){
            return false;
        }


        if($handler === null){
            unset($events);
        }
        else{
            $removed = false;
            foreach ($events as $key=>$event){
                if($event == $handler){
                    unset($events[$key]);
                    $removed = true;
                }
            }

            if($removed){
                $events = array_values($events);
            }
        }

        return $removed;
    }

    /**
     * 优先执行附加在实例上的事件,然后执行附加在类上的
     *
     * @param $name
     * @param null $class
     * @param null $event
     */
    public static function trigger($name,$class=null,array $triggerData=array())
    {
        if (empty(self::$_events[$name])){
            return;
        }

        if(!$class){
            //全局
            self::doTrigger(self::$_events[$name],$triggerData,$class);
        }
        else{
            //执行实例上绑定的事件
            if(is_object($class)){
                $hash =  spl_object_hash($class) ;
                self::doTrigger(self::$_events[$name][$hash],$triggerData,$class);
            }

            //执行类上绑定的事件
            $hash = is_object($class) ? get_class($class): ltrim($class,'\\');
            self::doTrigger(self::$_events[$name][$hash],$triggerData,$class);
        }
    }

    /**
     *只能注册一个事件,优先执行实例上的
     */
    public function triggerSingleton($name,$class=null,array $triggerData=array())
    {
        if (empty(self::$_events[$name])){
            return;
        }

        if(!$class){
            //全局
            return $this->doTriggerSingleton(self::$_events[$name] , $triggerData , $class);
        }
        else{
            //执行实例上绑定的事件
            if(is_object($class)){
                $hash =  spl_object_hash($class) ;
                return $this->doTriggerSingleton(self::$_events[$name][$hash] , $triggerData , $class);
            }
        }

        //执行类上绑定的事件
        $hash = is_object($class) ? get_class($class): ltrim($class,'\\');
        return $this->doTriggerSingleton(self::$_events[$name][$hash] , $triggerData , $class);
    }

    /**
     * 执行
     *
     * @param $handler
     * @param $data
     * @return mixed
     * @throws \Exception
     */
    public function doTriggerSingleton($handler , $triggerData , $class)
    {
        $data = $triggerData;
        if(!empty($handler[1]) && is_array($handler[1])){
            $data = array_merge($triggerData,$handler[1]);
        }
        //sender
        $data['sender'] = is_object($class) ? $class : null;

        return self::invoke($handler,$data);
    }

    /**
     * 遍历执行
     *
     * @param $events
     * @param $event
     */
    private static function doTrigger(&$events,$triggerData , $class){
        //遍历执行
        if($events){
            foreach ($events as $handler){
                $data = $handler[1];
                if($triggerData){
                    $data = array_merge($triggerData,$handler[1]);
                }
                //sender
                $data['sender'] = is_object($class) ? $class : null;
                self::invoke($handler[0],$data);
            }
        }

    }

    /**
     * 绑定参数,并调用方法
     *
     * @param $callable
     * @param $data      参数
     * @return mixed
     * @throws \Exception
     */
    public static function invoke($callable,$data)
    {
        $ref = self::getReflection($callable);

        $params = $ref->getParameters();
        $args   = array() ;
        foreach ($params as $param){
            $name = $param->getName();
            if (!empty($data[$name])){
                $args[] = $data[$name];
            }
            elseif($param->isDefaultValueAvailable()){
                $args[] = $param->getDefaultValue();
            }
            else{
                throw new \Exception('Function:'.$ref->getName().' , Missing parameter '.$name );
            }
        }

        return $ref->invokeArgs($args);
    }

    /**
     * 获取callable的反射类
     *
     * @param $callable
     * @return mixed|\ReflectionFunction|\ReflectionMethod
     */
    private static function getReflection($callable){
        if(is_array($callable)){
            $hash = spl_object_hash($callable[0]).$callable[1];
            if(!isset(self::$_reflections[$hash])){
                self::$_reflections[$hash] = new \ReflectionMethod($callable[0],$callable[1]);
            }
        }
        else{
            $hash = spl_object_hash($callable);
            if(!isset(self::$_reflections[$hash])){
                self::$_reflections[$hash] = new \ReflectionFunction($callable);
            }
        }

        return self::$_reflections[$hash];
    }

    /**
     * 检查切点是否织入了事件
     *
     * @param $name
     * @param null $handler
     * @param null $class
     * @return bool
     */
    public static function hasHandler($name,$handler=null,$class=null)
    {
        if(!$class){
            if(!empty(self::$_events[$name])){
                $events = self::$_events[$name];
            }
        }
        else{
            $hash = is_object($class) ? spl_object_hash($class) : ltrim($class,'\\');
            if(!empty(self::$_events[$name][$hash])){
                $events = self::$_events[$name][$hash];
            }
        }

        if($events){
            if($handler === null){
                return true;
            }
            else{
                foreach ($events as $hand){
                    if($hand == $handler){
                        return true;
                    }
                }
            }

        }

        return false;
    }

    /**
     * 返回所有切点和事件
     *
     * @return array
     */
    public function events()
    {
        return self::$_events;
    }

}
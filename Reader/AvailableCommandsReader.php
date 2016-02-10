<?php
/**
 * Created by PhpStorm.
 * User: AJanssen
 * Date: 10-02-16
 * Time: 13:25
 */

namespace EasyRules\SimpleBusBridgeBundle\Reader;

use SimpleBus\Message\CallableResolver\CallableMap;

/**
 * Class AvailableCommandsReader
 *
 * @package EasyRules\SimpleBusBridgeBundle\Reader
 */
class AvailableCommandsReader
{
    /**
     * @var \SimpleBus\Message\CallableResolver\CallableMap
     */
    private $map;
    /**
     * @var \SimpleBus\Message\CallableResolver\CallableMap
     */
    private $asynchronousMap;

    /**
     * AvailableCommandsReader constructor.
     *
     * @param \SimpleBus\Message\CallableResolver\CallableMap $map
     * @param \SimpleBus\Message\CallableResolver\CallableMap $asynchronousMap
     */
    public function __construct(CallableMap $map, CallableMap $asynchronousMap = null)
    {
        $this->map = $map;
        $this->asynchronousMap = $asynchronousMap;
    }

    public function getCommands()
    {
        $test = new \ReflectionClass($this->map);
        $property = $test->getProperty('callablesByName');
        $property->setAccessible(true);
        $commands = [];
        $commands = array_merge($property->getValue($this->map), $commands);
        $commands = array_merge($property->getValue($this->asynchronousMap), $commands);

        return $commands;
    }

}
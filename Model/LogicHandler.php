<?php
/**
 * Created by PhpStorm.
 * User: AJanssen
 * Date: 10-02-16
 * Time: 14:24
 */

namespace EasyRules\SimpleBusBridgeBundle\Model;


/**
 * Interface LogicHandler
 *
 * @package EasyRules\SimpleBusBridgeBundle\Model
 */
interface LogicHandler
{
    /**
     * @return mixed
     */
    public function getData();
}
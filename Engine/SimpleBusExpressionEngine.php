<?php
/**
 * Created by PhpStorm.
 * User: AJanssen
 * Date: 10-02-16
 * Time: 15:22
 */

namespace EasyRules\SimpleBusBridgeBundle\Engine;

use EasyRules\Engine\Engine;
use EasyRules\EngineBundle\Domain\Entity\Logic\Rule\Action;
use EasyRules\SimpleBusBridgeBundle\Exception\UnkownClassException;
use EasyRules\Engine\ExpressionLanguage\ExpressionLanguage;
use EasyRules\Engine\Model\Logic\Rule\ActionInterface;
use EasyRules\Engine\Model\LogicInterface;
use SimpleBus\Message\Bus\MessageBus;


/**
 * Class SimpleBusExpressionEngine
 *
 * @package EasyRules\SimpleBusBridgeBundle\Engine
 */
class SimpleBusExpressionEngine implements Engine
{
    /**
     * @var \EasyRules\Engine\ExpressionLanguage\ExpressionLanguage
     */
    protected $language;
    /**
     * @var \SimpleBus\Message\Bus\MessageBus
     */
    private $commandBus;
    /**
     * @var \SimpleBus\Message\Bus\MessageBus
     */
    private $eventBus;

    /**
     * ExpressionEngine constructor.
     *
     * @param \SimpleBus\Message\Bus\MessageBus $commandBus
     * @param \SimpleBus\Message\Bus\MessageBus $eventBus
     */
    public function __construct(MessageBus $commandBus, MessageBus $eventBus)
    {
        $this->language = new ExpressionLanguage();
        $this->commandBus = $commandBus;
        $this->eventBus = $eventBus;
    }

    /**
     * @param LogicInterface $logic
     * @param array          $parameters
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle(LogicInterface $logic, $parameters)
    {
        if (count($logic->getRules()) > 0) {
            foreach ($logic->getRules() as $rule) {
                $result = $this->language->evaluate(
                    $rule->getExpression(),
                    $parameters
                );
                foreach ($rule->getActions() as $action) {
                    if ($result == $action->getResult()) {
                        switch ($action->getType()) {
                            case ActionInterface::EXCEPTION:
                                throw new \Exception($action->getParameter());
                                break;
                            case ActionInterface::COMMAND:
                                $command = $this->createCommand($action, $parameters);
                                $this->commandBus->handle($command);
                                break;
                            case ActionInterface::EVENT:
                                $event = $this->createEvent($action, $parameters);
                                $this->eventBus->handle($event);
                                break;
                            default:
                                throw new \Exception("Unkown type");
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * @param \EasyRules\EngineBundle\Domain\Entity\Logic\Rule\Action $action
     * @param                                                                 $parameters
     *
     * @return mixed
     * @throws \EasyRules\SimpleBusBridgeBundle\Exception\UnkownClassException
     */
    private function createCommand(Action $action, $parameters)
    {
        $class = $action->getParameter();
        if (!class_exists($class)) {
            throw new UnkownClassException("Class {$class} doesn\t exist");
        }
        $command = new $class();
        $parameters['command'] = $command;

        $this->language->evaluate(
            $action->getCommandExpression(),
            $parameters
        );

        return $command;
    }

    /**
     * @param \EasyRules\EngineBundle\Domain\Entity\Logic\Rule\Action $action
     * @param                                                                 $parameters
     *
     * @return mixed
     * @throws \EasyRules\SimpleBusBridgeBundle\Exception\UnkownClassException
     */
    private function createEvent(Action $action, $parameters)
    {
        $class = $action->getParameter();
        if (!class_exists($class)) {
            throw new UnkownClassException("Class {$class} doesn\t exist");
        }
        $event = new $class();
        $parameters['event'] = $event;

        $this->language->evaluate(
            $action->getEventExpression(),
            $parameters
        );

        return $event;
    }
}
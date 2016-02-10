<?php
/**
 * Created by PhpStorm.
 * User: AJanssen
 * Date: 10-02-16
 * Time: 13:18
 */

namespace EasyRules\SimpleBusBridgeBundle\SimpleBus\Middleware;

use EasyRules\Engine\Engine;
use EasyRules\EngineBundle\Model\LogicRepositoryInterface;
use EasyRules\SimpleBusBridgeBundle\Model\LogicHandler;
use EasyRules\SimpleBusBridgeBundle\Model\LogicMessage;
use SimpleBus\Message\Bus\Middleware\MessageBusMiddleware;
use SimpleBus\Message\CallableResolver\Exception\UndefinedCallable;
use SimpleBus\Message\Handler\Resolver\MessageHandlerResolver;

/**
 * Class EasyRulesMiddleware
 *
 * @package EasyRules\SimpleBusBridgeBundle\SimpleBus\Middleware
 */
class EasyRulesMiddleware implements MessageBusMiddleware
{
    /**
     * @var \EasyRules\EngineBundle\Model\LogicRepositoryInterface
     */
    protected $logicRepository;
    /**
     * @var \EasyRules\Engine\Engine
     */
    protected $engine;
    /**
     * @var \SimpleBus\Message\Handler\Resolver\MessageHandlerResolver
     */
    protected $messageHandlerResolver;
    /**
     * @var \SimpleBus\Message\Handler\Resolver\MessageHandlerResolver
     */
    protected $asynchronousMessageHandlerResolver;

    /**
     * BusinessRulesMiddleware constructor.
     *
     * @param \EasyRules\EngineBundle\Model\LogicRepositoryInterface $logicRepository
     * @param \EasyRules\Engine\Engine                               $engine
     * @param \SimpleBus\Message\Handler\Resolver\MessageHandlerResolver     $messageHandlerResolver
     * @param \SimpleBus\Message\Handler\Resolver\MessageHandlerResolver     $asynchronousMessageHandlerResolver
     */
    public function __construct(LogicRepositoryInterface $logicRepository, Engine $engine, MessageHandlerResolver $messageHandlerResolver, MessageHandlerResolver $asynchronousMessageHandlerResolver)
    {
        $this->logicRepository                    = $logicRepository;
        $this->engine                             = $engine;
        $this->messageHandlerResolver             = $messageHandlerResolver;
        $this->asynchronousMessageHandlerResolver = $asynchronousMessageHandlerResolver;
    }

    /**
     * The provided $next callable should be called whenever the next middleware should start handling the message.
     * Its only argument should be a Message object (usually the same as the originally provided message).
     *
     * @param object   $message
     * @param callable $next
     *
     * @throws \Exception
     */
    public function handle($message, callable $next)
    {
        if ($message instanceof LogicMessage) {
            $logics = $this->logicRepository->byCommand(get_class($message));
            if (count($logics) > 0) {
                foreach ($logics as $logic) {
                    $handler = $this->getHandler($message);
                    if ($handler instanceof LogicHandler) {
                        $data = $handler->getData();
                    } else {
                        continue;
                    }
                    $data['message'] = $message;

                    $this->engine->handle($logic, $data);
                }
            }
        }
        $next($message);
    }

    /**
     * @param $message
     *
     * @return callable|void
     */
    protected function getHandler($message)
    {
        try {
            $handler = $this->messageHandlerResolver->resolve($message);
        } catch (UndefinedCallable $e) {
            try {
                $handler = $this->asynchronousMessageHandlerResolver->resolve($message);
                if (is_array($handler)) {
                    return current($handler);
                }
            } catch (UndefinedCallable $e) {
            }
        }
    }
}
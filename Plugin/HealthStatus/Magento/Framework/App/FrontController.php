<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2016 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Plugin\HealthStatus\Magento\Framework\App;

use Magento\Framework\Message\MessageInterface;
use Ess\M2ePro\Model\HealthStatus\Task\Result;

class FrontController extends \Ess\M2ePro\Plugin\AbstractPlugin
{
    const MESSAGE_IDENTIFIER = 'm2epro_health_status_front_controller_message';

    /** @var \Magento\Framework\Message\ManagerInterface */
    protected $messageManager;

    /** @var \Magento\Framework\UrlInterface */
    protected $urlBuilder;

    //########################################

    public function __construct(
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\UrlInterface $urlBuilder
    ) {
        $this->messageManager = $messageManager;
        $this->urlBuilder     = $urlBuilder;

        parent::__construct($helperFactory, $modelFactory);
    }

    //########################################

    public function aroundDispatch($interceptor, \Closure $callback, ...$arguments)
    {
        return $this->execute('dispatch', $interceptor, $callback, $arguments);
    }

    protected function processDispatch($interceptor, \Closure $callback, $arguments)
    {
        $request = isset($arguments[0]) ? $arguments[0] : NULL;

        if (!($request instanceof \Magento\Framework\App\Request\Http)) {
            return $callback(...$arguments);
        }

        if ($this->shouldBeAdded($request)) {

            $currentStatus  = $this->modelFactory->getObject('HealthStatus\CurrentStatus');
            $messageBuilder = $this->modelFactory->getObject('HealthStatus\Notification\MessageBuilder');

            switch ($currentStatus->get()) {
                case Result::STATE_NOTICE:
                    $messageType = MessageInterface::TYPE_NOTICE;
                    break;

                case Result::STATE_WARNING:
                    $messageType = MessageInterface::TYPE_WARNING;
                    break;

                default:
                case Result::STATE_CRITICAL:
                    $messageType = MessageInterface::TYPE_ERROR;
                    break;
            }

            $this->messageManager->addMessage(
                $this->messageManager->createMessage($messageType, self::MESSAGE_IDENTIFIER)
                     ->setText($messageBuilder->build())
            );
        }

        return $callback(...$arguments);
    }

    //########################################

    private function shouldBeAdded(\Magento\Framework\App\RequestInterface $request)
    {
        /** @var \Magento\Framework\App\Request\Http $request */

        if ($request->isPost() || $request->isAjax()) {
            return false;
        }

        // do not show on own page
        if (strpos($request->getPathInfo(), 'healthStatus') !== false) {
            return false;
        }

        $currentStatus = $this->modelFactory->getObject('HealthStatus\CurrentStatus');
        $notificationSettings = $this->modelFactory->getObject('HealthStatus\Notification\Settings');

        if (!$notificationSettings->isModeMagentoPages()) {
            return false;
        }

        if ($currentStatus->get() < $notificationSettings->getLevel()) {
            return false;
        }

        // after redirect message can be added twice
        foreach ($this->messageManager->getMessages()->getItems() as $message) {
            if ($message->getIdentifier() == self::MESSAGE_IDENTIFIER) {
                return false;
            }
        }

        return true;
    }

    //########################################
}
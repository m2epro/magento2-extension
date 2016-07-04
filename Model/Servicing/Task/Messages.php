<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Servicing\Task;

class Messages extends \Ess\M2ePro\Model\Servicing\Task
{
    //########################################

    /**
     * @return string
     */
    public function getPublicNick()
    {
        return 'messages';
    }

    protected $primaryConfig;

    //########################################

    public function __construct(
        \Ess\M2ePro\Model\Config\Manager\Primary $primaryConfig,
        \Magento\Eav\Model\Config $config,
        \Ess\M2ePro\Model\Config\Manager\Cache $cacheConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Ess\M2ePro\Model\Factory $modelFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Factory $parentFactory
    )
    {
        $this->primaryConfig = $primaryConfig;
        parent::__construct(
            $config,
            $cacheConfig,
            $storeManager,
            $modelFactory,
            $helperFactory,
            $resource,
            $activeRecordFactory,
            $parentFactory
        );
    }

    //########################################

    /**
     * @return array
     */
    public function getRequestData()
    {
        return array();
    }

    public function processResponseData(array $data)
    {
        $this->updateMagentoMessages($data);
        $this->updateModuleMessages($data);
    }

    //########################################

    private function updateMagentoMessages(array $messages)
    {
        $messages = array_filter($messages,array($this,'updateMagentoMessagesFilterMagentoMessages'));
        !is_array($messages) && $messages = array();

        $magentoTypes = array(
            \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_NOTICE =>
                \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE,
            \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_SUCCESS =>
                \Magento\Framework\Notification\MessageInterface::SEVERITY_NOTICE,
            \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_WARNING =>
                \Magento\Framework\Notification\MessageInterface::SEVERITY_MINOR,
            \Ess\M2ePro\Helper\Module::SERVER_MESSAGE_TYPE_ERROR =>
                \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL
        );

        foreach ($messages as $message) {
            $this->getHelper('Magento')->addGlobalNotification(
                $message['title'],
                $message['text'],
                $magentoTypes[$message['type']]
            );
        }
    }

    public function updateMagentoMessagesFilterMagentoMessages($message)
    {
        if (!isset($message['title']) || !isset($message['text']) || !isset($message['type'])) {
            return false;
        }

        if (!isset($message['is_global']) || !(bool)$message['is_global']) {
            return false;
        }

        return true;
    }

    //########################################

    private function updateModuleMessages(array $messages)
    {
        $messages = array_filter($messages,array($this,'updateModuleMessagesFilterModuleMessages'));
        !is_array($messages) && $messages = array();

        $this->primaryConfig->setGroupValue(
            '/'.$this->getHelper('Module')->getName().'/server/','messages',json_encode($messages)
        );
    }

    public function updateModuleMessagesFilterModuleMessages($message)
    {
        if (!isset($message['text']) || !isset($message['type'])) {
            return false;
        }

        if (isset($message['is_global']) && (bool)$message['is_global']) {
            return false;
        }

        return true;
    }

    //########################################
}
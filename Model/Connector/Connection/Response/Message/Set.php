<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Connector\Connection\Response\Message;

/**
 * @method \Ess\M2ePro\Model\Connector\Connection\Response\Message[] getErrorEntities
 */
class Set extends \Ess\M2ePro\Model\Response\Message\Set
{
    //########################################

    /** @return \Ess\M2ePro\Model\Connector\Connection\Response\Message */
    protected function getEntityModel()
    {
        return $this->modelFactory->getObject('Connector_Connection_Response_Message');
    }

    //########################################

    public function hasSystemErrorEntity()
    {
        foreach ($this->getErrorEntities() as $message) {
            if ($message->isSenderSystem()) {
                return true;
            }
        }

        return false;
    }

    public function getCombinedSystemErrorsString()
    {
        $messages = [];

        foreach ($this->getErrorEntities() as $message) {
            if (!$message->isSenderSystem()) {
                continue;
            }

            $messages[] = $message->getText();
        }

        return !empty($messages) ? implode(', ', $messages) : null;
    }

    //########################################
}

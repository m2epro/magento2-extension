<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Response\Message;

/**
 * Class \Ess\M2ePro\Model\Response\Message\Set
 */
class Set extends \Ess\M2ePro\Model\AbstractModel
{
    /** @var \Ess\M2ePro\Model\Response\Message[] $entities */
    protected $entities = [];

    //########################################

    public function init(array $responseData)
    {
        $this->clearEntities();

        foreach ($responseData as $messageData) {
            $message = $this->getEntityModel();
            $message->initFromResponseData($messageData);

            $this->entities[] = $message;
        }
    }

    /** @return \Ess\M2ePro\Model\Response\Message */
    protected function getEntityModel()
    {
        return $this->modelFactory->getObject('Response\Message');
    }

    //########################################

    public function addEntity(\Ess\M2ePro\Model\Response\Message $message)
    {
        $this->entities[] = $message;
    }

    public function clearEntities()
    {
        $this->entities = [];
    }

    //########################################

    public function getEntities()
    {
        return $this->entities;
    }

    public function getEntitiesAsArrays()
    {
        $result = [];

        foreach ($this->getEntities() as $message) {
            $result[] = $message->asArray();
        }

        return $result;
    }

    //########################################

    /**
     * @return \Ess\M2ePro\Model\Response\Message[]
     */
    public function getErrorEntities()
    {
        $messages = [];

        foreach ($this->getEntities() as $message) {
            $message->isError() && $messages[] = $message;
        }

        return $messages;
    }

    /**
     * @return \Ess\M2ePro\Model\Response\Message[]
     */
    public function getWarningEntities()
    {
        $messages = [];

        foreach ($this->getEntities() as $message) {
            $message->isWarning() && $messages[] = $message;
        }

        return $messages;
    }

    /**
     * @return \Ess\M2ePro\Model\Response\Message[]
     */
    public function getSuccessEntities()
    {
        $messages = [];

        foreach ($this->getEntities() as $message) {
            $message->isSuccess() && $messages[] = $message;
        }

        return $messages;
    }

    /**
     * @return \Ess\M2ePro\Model\Response\Message[]
     */
    public function getNoticeEntities()
    {
        $messages = [];

        foreach ($this->getEntities() as $message) {
            $message->isNotice() && $messages[] = $message;
        }

        return $messages;
    }

    // ########################################

    public function hasErrorEntities()
    {
        return !empty($this->getErrorEntities());
    }

    public function hasWarningEntities()
    {
        return !empty($this->getWarningEntities());
    }

    public function hasSuccessEntities()
    {
        return !empty($this->getSuccessEntities());
    }

    public function hasNoticeEntities()
    {
        return !empty($this->getNoticeEntities());
    }

    // ########################################

    public function getCombinedErrorsString()
    {
        $messages = [];

        foreach ($this->getErrorEntities() as $message) {
            $messages[] = $message->getText();
        }

        return !empty($messages) ? implode(', ', $messages) : null;
    }

    //########################################
}

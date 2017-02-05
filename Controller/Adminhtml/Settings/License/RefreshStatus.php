<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Settings\License;

class RefreshStatus extends \Ess\M2ePro\Controller\Adminhtml\Base
{
    public function execute()
    {
        try {
            $this->modelFactory->getObject('Servicing\Dispatcher')->processTask(
                $this->modelFactory->getObject('Servicing\Task\License')->getPublicNick()
            );
        } catch (\Exception $e) {
            $this->messageManager->addError(
                $this->__($e->getMessage())
            );

            $this->setJsonContent([
                'success' => false,
                'message' => $this->__($e->getMessage())
            ]);
            return $this->getResult();
        }

        $this->setJsonContent([
            'success' => true,
            'message' => $this->__('The License has been successfully refreshed.')
        ]);
        return $this->getResult();
    }
}
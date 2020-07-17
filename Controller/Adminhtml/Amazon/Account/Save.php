<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Account;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Amazon\Account\Save
 */
class Save extends Account
{
    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        // Add or update model
        // ---------------------------------------
        $model = $this->updateAccount($this->getRequest()->getParam('id'), $post->toArray());

        // Repricing
        // ---------------------------------------
        if (!empty($post['repricing']) && $model->getChildObject()->isRepricing()) {

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing $repricingModel */
            $repricingModel = $model->getChildObject()->getRepricing();

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Account_Repricing_SnapshotBuilder');
            $snapshotBuilder->setModel($repricingModel);

            $repricingOldData = $snapshotBuilder->getSnapshot();

            $this->modelFactory->getObject('Amazon_Account_Repricing_Builder')
                ->build($repricingModel, $post['repricing']);

            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Account_Repricing_SnapshotBuilder');
            $snapshotBuilder->setModel($repricingModel);

            $repricingNewData = $snapshotBuilder->getSnapshot();

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff $diff */
            $diff = $this->modelFactory->getObject('Amazon_Account_Repricing_Diff');
            $diff->setOldSnapshot($repricingOldData);
            $diff->setNewSnapshot($repricingNewData);

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts $affectedListingsProducts */
            $affectedListingsProducts = $this->modelFactory->getObject(
                'Amazon_Account_Repricing_AffectedListingsProducts'
            );
            $affectedListingsProducts->setModel($repricingModel);

            /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor $changeProcessor */
            $changeProcessor = $this->modelFactory->getObject('Amazon_Account_Repricing_ChangeProcessor');
            $changeProcessor->process($diff, $affectedListingsProducts->getObjectsData(['id', 'status']));
        }
        // ---------------------------------------

        try {
            // Add or update server
            // ---------------------------------------
            $this->sendDataToServer($model);
        } catch (\Exception $exception) {
            $this->getHelper('Module\Exception')->process($exception);

            $error = $this->__(
                'The Amazon access obtaining is currently unavailable.<br/>Reason: %error_message%',
                $exception->getMessage()
            );

            $model->delete();

            if ($this->isAjax()) {
                $this->setJsonContent([
                    'success' => false,
                    'message' => $error
                ]);
                return $this->getResult();
            }

            $this->messageManager->addError($error);

            return $this->_redirect('*/amazon_account');
        }

        if ($this->isAjax()) {
            $this->setJsonContent([
                'success' => true
            ]);
            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Account was successfully saved'));

        /** @var $wizardHelper \Ess\M2ePro\Helper\Module\Wizard */
        $wizardHelper = $this->getHelper('Module\Wizard');

        $routerParams = ['id' => $model->getId(), '_current' => true];
        if ($wizardHelper->isActive(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) &&
            $wizardHelper->getStep(\Ess\M2ePro\Helper\View\Amazon::WIZARD_INSTALLATION_NICK) == 'account') {
            $routerParams['wizard'] = true;
        }

        return $this->_redirect($this->getHelper('Data')->getBackUrl('list', [], ['edit'=>$routerParams]));
    }
}

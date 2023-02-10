<?php

/**
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer\Settings;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Repricer;

class Save extends Repricer
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder */
    private $repricingSnapshotBuilder;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff */
    private $repricingSnapshotDiff;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\Builder */
    private $repricingBuilder;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts */
    private $repricingAffectedListingsProducts;
    /** @var \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor */
    private $repricingChangeProcessor;

    /**
     * @param \Ess\M2ePro\Helper\Data $helperData
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff $repricingSnapshotDiff
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\Builder $repricingBuilder
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder $repricingSnapshotBuilder
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts $repricingAffectedListingsProducts
     * @param \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor $repricingChangeProcessor
     * @param \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory
     * @param \Ess\M2ePro\Controller\Adminhtml\Context $context
     */
    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\Diff $repricingSnapshotDiff,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\Builder $repricingBuilder,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\SnapshotBuilder $repricingSnapshotBuilder,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\AffectedListingsProducts $repricingAffectedListingsProducts,
        \Ess\M2ePro\Model\Amazon\Account\Repricing\ChangeProcessor $repricingChangeProcessor,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->helperData = $helperData;
        $this->repricingSnapshotBuilder = $repricingSnapshotBuilder;
        $this->repricingSnapshotDiff = $repricingSnapshotDiff;
        $this->repricingBuilder = $repricingBuilder;
        $this->repricingAffectedListingsProducts = $repricingAffectedListingsProducts;
        $this->repricingChangeProcessor = $repricingChangeProcessor;
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
        }

        $id = $this->getRequest()->getParam('id');

        /** @var \Ess\M2ePro\Model\Account $account */
        $account = $this->amazonFactory->getObjectLoaded('Account', $id);

        $repricingModel = $account->getChildObject()->getRepricing();

        $this->repricingSnapshotBuilder->setModel($repricingModel);

        $repricingOldData = $this->repricingSnapshotBuilder->getSnapshot();

        $this->repricingBuilder->build($repricingModel, $post['repricing']);

        $this->repricingSnapshotBuilder->setModel($repricingModel);

        $repricingNewData = $this->repricingSnapshotBuilder->getSnapshot();

        $this->repricingSnapshotDiff->setOldSnapshot($repricingOldData);
        $this->repricingSnapshotDiff->setNewSnapshot($repricingNewData);

        $this->repricingAffectedListingsProducts->setModel($repricingModel);

        $this->repricingChangeProcessor->process(
            $this->repricingSnapshotDiff,
            $this->repricingAffectedListingsProducts->getObjectsData(['id', 'status'])
        );

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true,
            ]);

            return $this->getResult();
        }

        $this->messageManager->addSuccess($this->__('Settings was saved'));

        return $this->_redirect(
            $this->helperData->getBackUrl('*/amazon_repricer_settings/index', [], [
                'edit' => [
                    'id' => $account->getId(),
                    'close_on_save' => $this->getRequest()->getParam('close_on_save'),
                ],
            ])
        );
    }
}

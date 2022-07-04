<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;

class Save extends Template
{
    /** @var \Ess\M2ePro\Helper\Component\Amazon\Configuration */
    protected $configuration;

    /** @var \Magento\Framework\Stdlib\DateTime */
    protected $dateTime;

    /** @var \Ess\M2ePro\Helper\Data */
    private $helperData;

    public function __construct(
        \Ess\M2ePro\Helper\Data $helperData,
        \Ess\M2ePro\Helper\Component\Amazon\Configuration $configuration,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($amazonFactory, $context);

        $this->configuration = $configuration;
        $this->dateTime = $dateTime;
        $this->helperData = $helperData;
    }

    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        // Add or update model
        // ---------------------------------------
        $model = $this->amazonFactory->getObject('Template\SellingFormat');

        $oldData = [];

        if ($id) {
            $model->load($id);
            /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\SnapshotBuilder $snapshotBuilder */
            $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_SellingFormat_SnapshotBuilder');
            $snapshotBuilder->setModel($model);
            $oldData = $snapshotBuilder->getSnapshot();
        }

        $this->modelFactory->getObject('Amazon_Template_SellingFormat_Builder')->build($model, $post->toArray());

        if ($this->configuration->isEnabledBusinessMode()) {
            $this->saveDiscounts($model->getId(), $post);
        }

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\SnapshotBuilder $snapshotBuilder */
        $snapshotBuilder = $this->modelFactory->getObject('Amazon_Template_SellingFormat_SnapshotBuilder');
        $snapshotBuilder->setModel($model);
        $newData = $snapshotBuilder->getSnapshot();

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\Diff $diff */
        $diff = $this->modelFactory->getObject('Amazon_Template_SellingFormat_Diff');
        $diff->setNewSnapshot($newData);
        $diff->setOldSnapshot($oldData);

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\AffectedListingsProducts $affectedListingsProducts */
        $affectedListingsProducts = $this->modelFactory->getObject(
            'Amazon_Template_SellingFormat_AffectedListingsProducts'
        );
        $affectedListingsProducts->setModel($model);

        /** @var \Ess\M2ePro\Model\Amazon\Template\SellingFormat\ChangeProcessor $changeProcessor */
        $changeProcessor = $this->modelFactory->getObject('Amazon_Template_SellingFormat_ChangeProcessor');
        $changeProcessor->process(
            $diff,
            $affectedListingsProducts->getObjectsData(['id', 'status'], ['only_physical_units' => true])
        );

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $id = $model->getId();

        $this->messageManager->addSuccess($this->__('Policy was saved'));
        return $this->_redirect($this->helperData->getBackUrl('*/amazon_template/index', [], [
            'edit' => [
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ],
        ]));
    }

    //########################################

    private function saveDiscounts($templateId, $post)
    {
        $amazonTemplateSellingFormatBusinessDiscountTable = $this->activeRecordFactory->getObject(
            'Amazon_Template_SellingFormat_BusinessDiscount'
        )->getResource()->getMainTable();

        $this->resourceConnection->getConnection()->delete(
            $amazonTemplateSellingFormatBusinessDiscountTable,
            [
                'template_selling_format_id = ?' => (int)$templateId
            ]
        );

        if (empty($post['is_business_customer_allowed']) ||
            empty($post['business_discount']) || empty($post['business_discount']['qty'])
        ) {
            return;
        }

        $discounts = [];
        foreach ($post['business_discount']['qty'] as $i => $qty) {
            if ((string)$i == '%i%') {
                continue;
            }

            $attribute = empty($post['business_discount']['attribute']) ?
                '' : $post['business_discount']['attribute'][$i];

            $mode = empty($post['business_discount']['mode'][$i]) ?
                '' : $post['business_discount']['mode'][$i];

            $coefficient = empty($post['business_discount']['coefficient'][$i]) ?
                '' : $post['business_discount']['coefficient'][$i];

            $discounts[] = [
                'template_selling_format_id' => $templateId,
                'qty'                        => $qty,
                'mode'                       => $mode,
                'attribute'                  => $attribute,
                'coefficient'                => $coefficient
            ];
        }

        if (empty($discounts)) {
            return;
        }

        usort($discounts, function ($a, $b) {
            return $a["qty"] <=> $b["qty"];
        });

        $this->resourceConnection->getConnection()->insertMultiple(
            $amazonTemplateSellingFormatBusinessDiscountTable,
            $discounts
        );
    }

    //########################################
}

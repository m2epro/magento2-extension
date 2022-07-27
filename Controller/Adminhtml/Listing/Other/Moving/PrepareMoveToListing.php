<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Listing\Other\Moving;

class PrepareMoveToListing extends \Ess\M2ePro\Controller\Adminhtml\Listing
{
    /** @var \Ess\M2ePro\Helper\Data\Session */
    private $sessionHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructureHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data\Session $sessionHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($context);

        $this->sessionHelper = $sessionHelper;
        $this->dbStructureHelper = $dbStructureHelper;
    }

    public function execute()
    {
        $componentMode = $this->getRequest()->getParam('componentMode');
        $sessionKey = $componentMode . '_' . \Ess\M2ePro\Helper\View::MOVING_LISTING_OTHER_SELECTED_SESSION_KEY;

        if ((bool)$this->getRequest()->getParam('is_first_part')) {
            $this->sessionHelper->removeValue($sessionKey);
        }

        $selectedProducts = [];
        if ($sessionValue = $this->sessionHelper->getValue($sessionKey)) {
            $selectedProducts = $sessionValue;
        }

        $selectedProductsPart = $this->getRequest()->getParam('products_part');
        $selectedProductsPart = explode(',', $selectedProductsPart);

        $selectedProducts = array_merge($selectedProducts, $selectedProductsPart);
        $this->sessionHelper->setValue($sessionKey, $selectedProducts);

        if (!(bool)$this->getRequest()->getParam('is_last_part')) {
            $this->setJsonContent(['result' => true]);

            return $this->getResult();
        }

        $listingOtherCollection = $this->parentFactory
            ->getObject($componentMode, 'Listing\Other')
            ->getCollection();

        $listingOtherCollection->addFieldToFilter('main_table.id', ['in' => $selectedProducts]);
        $listingOtherCollection->addFieldToFilter('main_table.product_id', ['notnull' => true]);

        if ($listingOtherCollection->getSize() != count($selectedProducts)) {
            $this->sessionHelper->removeValue($sessionKey);

            $this->setJsonContent(
                [
                    'result'  => false,
                    'message' => $this->__('Only Linked Products must be selected.'),
                ]
            );

            return $this->getResult();
        }

        $listingOtherCollection->getSelect()->join(
            [
                'cpe' => $this->dbStructureHelper->getTableNameWithPrefix('catalog_product_entity'),
            ],
            '`main_table`.`product_id` = `cpe`.`entity_id`'
        );

        $row = $listingOtherCollection
            ->getSelect()
            ->group(['main_table.account_id', 'main_table.marketplace_id'])
            ->reset(\Magento\Framework\DB\Select::COLUMNS)
            ->columns(['marketplace_id', 'account_id'])
            ->query()
            ->fetch();

        $response = [
            'result'        => true,
            'accountId'     => (int)$row['account_id'],
            'marketplaceId' => (int)$row['marketplace_id'],
        ];

        $this->setJsonContent($response);

        return $this->getResult();
    }
}

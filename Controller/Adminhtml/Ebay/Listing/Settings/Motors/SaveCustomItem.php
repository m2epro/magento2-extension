<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Ebay\Listing\Settings\Motors;

class SaveCustomItem extends \Ess\M2ePro\Controller\Adminhtml\Ebay\Listing
{
    /** @var \Ess\M2ePro\Helper\Component\Ebay\Motors */
    private $componentEbayMotors;

    public function __construct(
        \Ess\M2ePro\Helper\Component\Ebay\Motors $componentEbayMotors,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($ebayFactory, $context);

        $this->componentEbayMotors = $componentEbayMotors;
    }

    public function execute()
    {
        $helper = $this->componentEbayMotors;
        $motorsType = $this->getRequest()->getParam('motors_type');

        $tableName = $helper->getDictionaryTable($motorsType);
        $idKey = $helper->getIdentifierKey($motorsType);

        $insertData = $this->getRequest()->getParam('item', []);
        foreach ($insertData as &$item) {
            $item == '' && $item = null;
        }
        $insertData['is_custom'] = 1;

        if ($helper->isTypeBasedOnEpids($motorsType)) {
            $insertData['scope'] = $helper->getEpidsScopeByType($motorsType);
        }

        if ($motorsType == \Ess\M2ePro\Helper\Component\Ebay\Motors::TYPE_KTYPE) {
            if (strlen($insertData['ktype']) > 10) {
                $this->setJsonContent([
                    'result'  => false,
                    'message' => $this->__('kType identifier is to long.')
                ]);
                return $this->getResult();
            }

            if (!is_numeric($insertData['ktype'])) {
                $this->setJsonContent([
                    'result'  => false,
                    'message' => $this->__('kType identifier should contain only digits.')
                ]);
                return $this->getResult();
            }
        }

        $selectStmt = $this->resourceConnection->getConnection('core/read')
            ->select()
            ->from($tableName)
            ->where("{$idKey} = ?", $insertData[$idKey]);

        if ($helper->isTypeBasedOnEpids($motorsType)) {
            $selectStmt->where('scope = ?', $helper->getEpidsScopeByType($motorsType));
        }

        $existedItem = $selectStmt->query()->fetch();

        if ($existedItem) {
            $this->setJsonContent([
                'result'  => false,
                'message' => $this->__('Record with such identifier is already exists.')
            ]);

            return $this->getResult();
        }

        $connection = $this->resourceConnection->getConnection();
        $connection->insert($tableName, $insertData);

        $this->setJsonContent(['result' => true]);

        return $this->getResult();
    }
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

class GetAllSpecifics extends Category
{
    /** @var \Ess\M2ePro\Helper\Data */
    private $dataHelper;

    /** @var \Ess\M2ePro\Helper\Module\Database\Structure */
    private $dbStructureHelper;

    public function __construct(
        \Ess\M2ePro\Helper\Data $dataHelper,
        \Ess\M2ePro\Helper\Module\Database\Structure $dbStructureHelper,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Walmart\Factory $walmartFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    ) {
        parent::__construct($walmartFactory, $context);

        $this->dataHelper = $dataHelper;
        $this->dbStructureHelper = $dbStructureHelper;
    }

    public function execute()
    {
        $tempSpecifics = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->dbStructureHelper->getTableNameWithPrefix('m2epro_walmart_dictionary_specific')
            )
            ->where('marketplace_id = ?', $this->getRequest()->getParam('marketplace_id'))
            ->where('product_data_nick = ?', $this->getRequest()->getParam('product_data_nick'))
            ->query()->fetchAll();

        $specifics = [];
        foreach ($tempSpecifics as $tempSpecific) {
            $tempSpecific['values']             = (array)$this->dataHelper->jsonDecode($tempSpecific['values']);
            $tempSpecific['recommended_values'] = (array)$this->dataHelper->jsonDecode(
                $tempSpecific['recommended_values']
            );
            $tempSpecific['params']          = (array)$this->dataHelper->jsonDecode($tempSpecific['params']);
            $tempSpecific['data_definition'] = (array)$this->dataHelper->jsonDecode($tempSpecific['data_definition']);

            $specifics[$tempSpecific['specific_id']] = $tempSpecific;
        }

        $this->setJsonContent($specifics);
        return $this->getResult();
    }
}

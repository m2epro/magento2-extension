<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

use Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category;

/**
 * Class \Ess\M2ePro\Controller\Adminhtml\Walmart\Template\Category\GetAllSpecifics
 */
class GetAllSpecifics extends Category
{
    //########################################

    public function execute()
    {
        $tempSpecifics = $this->resourceConnection->getConnection()->select()
            ->from(
                $this->getHelper('Module_Database_Structure')
                    ->getTableNameWithPrefix('m2epro_walmart_dictionary_specific')
            )
            ->where('marketplace_id = ?', $this->getRequest()->getParam('marketplace_id'))
            ->where('product_data_nick = ?', $this->getRequest()->getParam('product_data_nick'))
            ->query()->fetchAll();

        $specifics = [];
        foreach ($tempSpecifics as $tempSpecific) {
            $tempSpecific['values']             = (array)$this->getHelper('Data')->jsonDecode($tempSpecific['values']);
            $tempSpecific['recommended_values'] = (array)$this->getHelper('Data')->jsonDecode(
                $tempSpecific['recommended_values']
            );
            $tempSpecific['params']             = (array)$this->getHelper('Data')->jsonDecode($tempSpecific['params']);
            $tempSpecific['data_definition']    = (array)$this->getHelper('Data')->jsonDecode(
                $tempSpecific['data_definition']
            );

            $specifics[$tempSpecific['specific_id']] = $tempSpecific;
        }

        $this->setJsonContent($specifics);
        return $this->getResult();
    }

    //########################################
}

<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template\Description;

class GetAllSpecifics extends Description
{
    //########################################

    public function execute()
    {
        $tempSpecifics = $this->resourceConnection->getConnection()->select()
            ->from($this->resourceConnection->getTableName('m2epro_amazon_dictionary_specific'))
            ->where('marketplace_id = ?', $this->getRequest()->getParam('marketplace_id'))
            ->where('product_data_nick = ?', $this->getRequest()->getParam('product_data_nick'))
            ->query()->fetchAll();

        $specifics = array();
        foreach ($tempSpecifics as $tempSpecific) {

            $tempSpecific['values']             = (array)json_decode($tempSpecific['values'], true);
            $tempSpecific['recommended_values'] = (array)json_decode($tempSpecific['recommended_values'], true);
            $tempSpecific['params']             = (array)json_decode($tempSpecific['params'], true);
            $tempSpecific['data_definition']    = (array)json_decode($tempSpecific['data_definition'], true);

            $specifics[$tempSpecific['specific_id']] = $tempSpecific;
        }

        $this->setJsonContent($specifics);
        return $this->getResult();
    }

    //########################################
}
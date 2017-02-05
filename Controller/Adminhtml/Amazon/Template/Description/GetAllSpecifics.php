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
<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Title
 */
class Title extends AbstractModel
{
    //########################################

    public function getBuilderData()
    {
        $this->searchNotFoundAttributes();
        $data = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getTitle();
        $this->processNotFoundAttributes('Title');

        return [
            'title' => $data
        ];
    }

    //########################################
}

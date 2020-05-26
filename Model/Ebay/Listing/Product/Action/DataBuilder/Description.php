<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Ebay\Listing\Product\Action\DataBuilder\Description
 */
class Description extends AbstractModel
{
    //########################################

    public function getBuilderData()
    {
        $this->searchNotFoundAttributes();

        $data = $this->getEbayListingProduct()->getDescriptionTemplateSource()->getDescription();
        $data = $this->getEbayListingProduct()->getDescriptionRenderer()->parseTemplate($data);

        $this->processNotFoundAttributes('Description');

        return [
            'description' => $data
        ];
    }

    //########################################
}

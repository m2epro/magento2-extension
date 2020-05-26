<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder;

/**
 * Class \Ess\M2ePro\Model\Amazon\Listing\Product\Action\DataBuilder\Images
 */
class Images extends AbstractModel
{
    //########################################

    /**
     * @return array
     * @throws \Ess\M2ePro\Model\Exception
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function getBuilderData()
    {
        $data = [];

        $this->searchNotFoundAttributes();

        $links = [];
        foreach ($this->getAmazonListingProduct()->getListingSource()->getGalleryImages() as $image) {
            if (!$image->getUrl()) {
                continue;
            }

            $links[] = $image->getUrl();
        }

        $images = ['offer' => $links,];

        if ($this->getAmazonListingProduct()->isExistDescriptionTemplate()) {
            $amazonDescriptionTemplate = $this->getAmazonListingProduct()->getAmazonDescriptionTemplate();
            $definitionSource = $amazonDescriptionTemplate->getDefinitionTemplate()->getSource(
                $this->getAmazonListingProduct()->getActualMagentoProduct()
            );

            $links = [];
            foreach ($definitionSource->getGalleryImages() as $image) {
                if (!$image->getUrl()) {
                    continue;
                }

                $links[] = $image->getUrl();
            }

            $images['product'] = $links;

            if ($this->getVariationManager()->isRelationChildType()) {
                $links = [];
                foreach ($definitionSource->getVariationDifferenceImages() as $image) {
                    if (!$image->getUrl()) {
                        continue;
                    }

                    $links[] = $image->getUrl();
                }

                $images['variation_difference'] = $links;
            }
        }

        $this->processNotFoundAttributes('Images');

        if (!empty($images['offer'])) {
            $data['images_data']['offer'] = $images['offer'];
        }

        if (!empty($images['product'])) {
            $data['images_data']['product'] = $images['product'];
        }

        if (!empty($images['variation_difference'])) {
            $data['images_data']['variation_difference'] = $images['variation_difference'];
        }

        return $data;
    }

    //########################################
}

<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Block\Adminhtml\Renderer\Description;

/**
 * Class \Ess\M2ePro\Block\Adminhtml\Renderer\Description\Image
 */
class Image extends \Ess\M2ePro\Block\Adminhtml\Renderer\Description
{
    private $imageId;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('rendererDescriptionImage');
        // ---------------------------------------

        $this->setTemplate('Ess_M2ePro::renderer/description/image.phtml');
    }

    //########################################

    public function getImageId()
    {
        if ($this->imageId === null) {
            $this->imageId = substr(sha1(
                'image-'
                . $this->getData('index_number')
                . $this->getHelper('Data')->jsonEncode($this->getData('src'))
            ), 20);
        }
        return $this->imageId;
    }

    //########################################

    public function isModeDefault()
    {
        return $this->getData('linked_mode') == \Ess\M2ePro\Helper\Module\Renderer\Description::IMAGES_MODE_DEFAULT;
    }

    //########################################
}

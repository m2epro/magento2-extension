<?php

namespace Ess\M2ePro\Block\Adminhtml\Renderer\Description;

class Gallery extends \Ess\M2ePro\Block\Adminhtml\Renderer\Description
{
    private $galleryId;

    //########################################

    public function _construct()
    {
        parent::_construct();

        // Initialization block
        // ---------------------------------------
        $this->setId('rendererDescriptionGallery');
        // ---------------------------------------

        $this->setTemplate('renderer/description/gallery.phtml');
    }

    //########################################

    public function getGalleryId()
    {
        if (is_null($this->galleryId)) {
            $this->galleryId = substr(sha1(
                'gallery-'
                . $this->getData('index_number')
                . $this->getHelper('Data')->jsonEncode($this->getGalleryImages())
            ), 20);
        }
        return $this->galleryId;
    }

    //########################################

    public function isModeGallery()
    {
        return $this->getData('linked_mode') == \Ess\M2ePro\Helper\Module\Renderer\Description::IMAGES_MODE_GALLERY;
    }

    public function isLinkMode()
    {
        return $this->getData('linked_mode') == \Ess\M2ePro\Helper\Module\Renderer\Description::IMAGES_MODE_NEW_WINDOW;
    }

    public function isLayoutColumnMode()
    {
        return $this->getData('layout') == \Ess\M2ePro\Helper\Module\Renderer\Description::LAYOUT_MODE_COLUMN;
    }

    //########################################

    public function getGalleryImages()
    {
        return $this->getData('images') ? $this->getData('images') : [];
    }

    //########################################
}
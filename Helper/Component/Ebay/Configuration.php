<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Helper\Component\Ebay;

/**
 * Class \Ess\M2ePro\Helper\Component\Ebay\Configuration
 */
class Configuration extends \Ess\M2ePro\Helper\AbstractHelper
{
    const UPLOAD_IMAGES_MODE_AUTO = 1;
    const UPLOAD_IMAGES_MODE_SELF = 2;
    const UPLOAD_IMAGES_MODE_EPS  = 3;

    const CONFIG_GROUP = '/ebay/configuration/';

    //########################################

    public function getFeedbackNotificationMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'feedback_notification_mode'
        );
    }

    public function isEnableFeedbackNotificationMode()
    {
        return $this->getFeedbackNotificationMode() == 1;
    }

    /**
     * @param string $value
     *
     * @return $this
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setFeedbackNotificationLastCheck($value)
    {
        $this->getHelper('Module')->getConfig()->setGroupValue(
            self::CONFIG_GROUP,
            'feedback_notification_last_check',
            $value
        );

        return $this;
    }

    public function getFeedbackNotificationLastCheck()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'feedback_notification_last_check'
        );
    }

    public function getPreventItemDuplicatesMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'prevent_item_duplicates_mode'
        );
    }

    public function isEnablePreventItemDuplicatesMode()
    {
        return $this->getPreventItemDuplicatesMode() == 1;
    }

    public function getUploadImagesMode()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'upload_images_mode'
        );
    }

    public function isAutoUploadImagesMode()
    {
        return $this->getUploadImagesMode() == self::UPLOAD_IMAGES_MODE_AUTO;
    }

    public function isSelfUploadImagesMode()
    {
        return $this->getUploadImagesMode() == self::UPLOAD_IMAGES_MODE_SELF;
    }

    public function isEpsUploadImagesMode()
    {
        return $this->getUploadImagesMode() == self::UPLOAD_IMAGES_MODE_EPS;
    }

    public function getUkEpidsAttribute()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'uk_epids_attribute'
        );
    }

    public function getDeEpidsAttribute()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'de_epids_attribute'
        );
    }

    public function getAuEpidsAttribute()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'au_epids_attribute'
        );
    }

    public function getMotorsEpidsAttribute()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'motors_epids_attribute'
        );
    }

    public function getKTypesAttribute()
    {
        return $this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'ktypes_attribute'
        );
    }

    //########################################

    public function getViewTemplateSellingFormatShowTaxCategory()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'view_template_selling_format_show_tax_category'
        );
    }

    public function getVariationMpnCanBeChanged()
    {
        return (int)$this->getHelper('Module')->getConfig()->getGroupValue(
            self::CONFIG_GROUP,
            'variation_mpn_can_be_changed'
        );
    }

    //########################################

    /**
     * @param array $values
     *
     * @throws \Ess\M2ePro\Model\Exception\Logic
     */
    public function setConfigValues(array $values)
    {
        if (isset($values['feedback_notification_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'feedback_notification_mode',
                $values['feedback_notification_mode']
            );
        }

        if (isset($values['feedback_notification_last_check'])) {
            $this->setFeedbackNotificationLastCheck($values['feedback_notification_last_check']);
        }

        if (isset($values['prevent_item_duplicates_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'prevent_item_duplicates_mode',
                $values['prevent_item_duplicates_mode']
            );
        }

        if (isset($values['upload_images_mode'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'upload_images_mode',
                $values['upload_images_mode']
            );
        }

        //----------------------------------------

        $motorsAttributes = [];

        if (isset($values['uk_epids_attribute'])) {
            $motorsAttributes[] = $values['uk_epids_attribute'];
        }

        if (isset($values['de_epids_attribute'])) {
            $motorsAttributes[] = $values['de_epids_attribute'];
        }

        if (isset($values['au_epids_attribute'])) {
            $motorsAttributes[] = $values['au_epids_attribute'];
        }

        if (isset($values['motors_epids_attribute'])) {
            $motorsAttributes[] = $values['motors_epids_attribute'];
        }

        if (isset($values['ktypes_attribute'])) {
            $motorsAttributes[] = $values['ktypes_attribute'];
        }

        if (count(array_filter($motorsAttributes)) != count(array_unique(array_filter($motorsAttributes)))) {
            throw new \Ess\M2ePro\Model\Exception\Logic('Motors Attributes can not be the same.');
        }

        if (isset($values['uk_epids_attribute'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'uk_epids_attribute',
                $values['uk_epids_attribute']
            );
        }

        if (isset($values['de_epids_attribute'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'de_epids_attribute',
                $values['de_epids_attribute']
            );
        }

        if (isset($values['au_epids_attribute'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'au_epids_attribute',
                $values['au_epids_attribute']
            );
        }

        if (isset($values['motors_epids_attribute'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'motors_epids_attribute',
                $values['motors_epids_attribute']
            );
        }

        if (isset($values['ktypes_attribute'])) {
            $this->getHelper('Module')->getConfig()->setGroupValue(
                self::CONFIG_GROUP,
                'ktypes_attribute',
                $values['ktypes_attribute']
            );
        }
    }

    //########################################
}

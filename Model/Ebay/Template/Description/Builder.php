<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  M2E LTD
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Description;

use Ess\M2ePro\Model\Ebay\Template\Description as Description;

/**
 * Class \Ess\M2ePro\Model\Ebay\Template\Description\Builder
 */
class Builder extends \Ess\M2ePro\Model\Ebay\Template\AbstractBuilder
{
    protected $driverPool;
    protected $phpEnvironmentRequest;

    public function __construct(
        \Magento\Framework\Filesystem\DriverPool $driverPool,
        \Magento\Framework\HTTP\PhpEnvironment\Request $phpEnvironmentRequest,
        \Ess\M2ePro\Model\ActiveRecord\Factory $activeRecordFactory,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Ebay\Factory $ebayFactory,
        \Ess\M2ePro\Helper\Factory $helperFactory,
        \Ess\M2ePro\Model\Factory $modelFactory
    ) {
        $this->driverPool = $driverPool;
        $this->phpEnvironmentRequest = $phpEnvironmentRequest;
        parent::__construct($activeRecordFactory, $ebayFactory, $helperFactory, $modelFactory);
    }

    //########################################

    protected function prepareData()
    {
        $data = parent::prepareData();

        $defaultData = $this->getDefaultData();

        $data = $this->getHelper('Data')->arrayReplaceRecursive($defaultData, $data);

        if (isset($this->rawData['title_mode'])) {
            $data['title_mode'] = (int)$this->rawData['title_mode'];
        }

        if (isset($this->rawData['title_template'])) {
            $data['title_template'] = $this->rawData['title_template'];
        }

        if (isset($this->rawData['subtitle_mode'])) {
            $data['subtitle_mode'] = (int)$this->rawData['subtitle_mode'];
        }

        if (isset($this->rawData['subtitle_template'])) {
            $data['subtitle_template'] = $this->rawData['subtitle_template'];
        }

        if (isset($this->rawData['description_mode'])) {
            $data['description_mode'] = (int)$this->rawData['description_mode'];
        }

        if (isset($this->rawData['description_template'])) {
            $data['description_template'] = $this->rawData['description_template'];
        }

        if (isset($this->rawData['condition_mode'])) {
            $data['condition_mode'] = (int)$this->rawData['condition_mode'];
        }

        if (isset($this->rawData['condition_value'])) {
            $data['condition_value'] = (int)$this->rawData['condition_value'];
        }

        if (isset($this->rawData['condition_attribute'])) {
            $data['condition_attribute'] = $this->rawData['condition_attribute'];
        }

        if (isset($this->rawData['condition_note_mode'])) {
            $data['condition_note_mode'] = (int)$this->rawData['condition_note_mode'];
        }

        if (isset($this->rawData['condition_note_template'])) {
            $data['condition_note_template'] = $this->rawData['condition_note_template'];
        }

        if (isset($this->rawData['product_details'])) {
            $data['product_details'] = $this->rawData['product_details'];

            if (is_array($data['product_details'])) {
                $data['product_details'] = $this->getHelper('Data')->jsonEncode($data['product_details']);
            }
        }

        if (isset($this->rawData['editor_type'])) {
            $data['editor_type'] = (int)$this->rawData['editor_type'];
        }

        if (isset($this->rawData['cut_long_titles'])) {
            $data['cut_long_titles'] = (int)$this->rawData['cut_long_titles'];
        }

        if (isset($this->rawData['hit_counter'])) {
            $data['hit_counter'] = $this->rawData['hit_counter'];
        }

        if (isset($this->rawData['enhancement'])) {
            $data['enhancement'] = $this->rawData['enhancement'];

            if (is_array($data['enhancement'])) {
                $data['enhancement'] = implode(',', $this->rawData['enhancement']);
            }
        }

        if (isset($this->rawData['gallery_type'])) {
            $data['gallery_type'] = (int)$this->rawData['gallery_type'];
        }

        if (isset($this->rawData['image_main_mode'])) {
            $data['image_main_mode'] = (int)$this->rawData['image_main_mode'];
        }

        if (isset($this->rawData['image_main_attribute'])) {
            $data['image_main_attribute'] = $this->rawData['image_main_attribute'];
        }

        if (isset($this->rawData['gallery_images_mode'])) {
            $data['gallery_images_mode'] = (int)$this->rawData['gallery_images_mode'];
        }

        if (isset($this->rawData['gallery_images_limit'])) {
            $data['gallery_images_limit'] = (int)$this->rawData['gallery_images_limit'];
        }

        if (isset($this->rawData['gallery_images_attribute'])) {
            $data['gallery_images_attribute'] = $this->rawData['gallery_images_attribute'];
        }

        if (isset($this->rawData['variation_images_mode'])) {
            $data['variation_images_mode'] = (int)$this->rawData['variation_images_mode'];
        }

        if (isset($this->rawData['variation_images_limit'])) {
            $data['variation_images_limit'] = (int)$this->rawData['variation_images_limit'];
        }

        if (isset($this->rawData['variation_images_attribute'])) {
            $data['variation_images_attribute'] = $this->rawData['variation_images_attribute'];
        }

        if (isset($this->rawData['reserve_price_custom_attribute'])) {
            $data['reserve_price_custom_attribute'] = $this->rawData['reserve_price_custom_attribute'];
        }

        if (isset($this->rawData['default_image_url'])) {
            $data['default_image_url'] = $this->rawData['default_image_url'];
        }

        if (isset($this->rawData['variation_configurable_images'])) {
            $data['variation_configurable_images'] = $this->rawData['variation_configurable_images'];

            if (is_array($data['variation_configurable_images'])) {
                $data['variation_configurable_images'] = $this->getHelper('Data')->jsonEncode(
                    $data['variation_configurable_images']
                );
            }
        }

        if (isset($this->rawData['use_supersize_images'])) {
            $data['use_supersize_images'] = (int)$this->rawData['use_supersize_images'];
        }

        if (isset($this->rawData['watermark_mode'])) {
            $data['watermark_mode'] = (int)$this->rawData['watermark_mode'];
        }

        // ---------------------------------------

        $watermarkSettings = [];
        $hashChange = false;

        if (isset($this->rawData['watermark_settings']['position'])) {
            $watermarkSettings['position'] = (int)$this->rawData['watermark_settings']['position'];

            if (isset($this->rawData['old_watermark_settings']) &&
                $this->rawData['watermark_settings']['position'] !==
                $this->rawData['old_watermark_settings']['position']) {
                $hashChange = true;
            }
        }

        if (isset($this->rawData['watermark_settings']['scale'])) {
            $watermarkSettings['scale'] = (int)$this->rawData['watermark_settings']['scale'];

            if (isset($this->rawData['old_watermark_settings']) &&
                $this->rawData['watermark_settings']['scale'] !== $this->rawData['old_watermark_settings']['scale']) {
                $hashChange = true;
            }
        }

        if (isset($this->rawData['watermark_settings']['transparent'])) {
            $watermarkSettings['transparent'] = (int)$this->rawData['watermark_settings']['transparent'];

            if (isset($this->rawData['old_watermark_settings']) &&
                $this->rawData['watermark_settings']['transparent'] !==
                $this->rawData['old_watermark_settings']['transparent']) {
                $hashChange = true;
            }
        }

        // ---------------------------------------

        $watermarkImageFile = $this->phpEnvironmentRequest->getFiles('watermark_image');

        if (!empty($watermarkImageFile['tmp_name'])) {
            $hashChange = true;

            $data['watermark_image'] = file_get_contents($watermarkImageFile['tmp_name']);

            if (isset($data['id'])) {
                /** @var \Ess\M2ePro\Model\VariablesDir $varDir */
                $varDir = $this->modelFactory->getObject('VariablesDir', ['data' => [
                    'child_folder' => 'ebay/template/description/watermarks'
                ]]);

                $watermarkPath = $varDir->getPath().(int)$data['id'].'.png';

                $fileDriver = $this->driverPool->getDriver(\Magento\Framework\Filesystem\DriverPool::FILE);
                if ($fileDriver->isFile($watermarkPath)) {
                    $fileDriver->deleteFile($watermarkPath);
                }
            }
        } elseif (!empty($this->rawData['old_watermark_image']) && isset($data['id'])) {
            $data['watermark_image'] = base64_decode($this->rawData['old_watermark_image']);
        }

        // ---------------------------------------

        if ($hashChange) {
            $watermarkSettings['hashes']['previous'] = $this->rawData['old_watermark_settings']['hashes']['current'];
            $watermarkSettings['hashes']['current'] = substr(sha1(microtime()), 0, 5);
        } else {
            $watermarkSettings['hashes']['previous'] = $this->rawData['old_watermark_settings']['hashes']['previous'];
            $watermarkSettings['hashes']['current'] = $this->rawData['old_watermark_settings']['hashes']['current'];
        }

        $data['watermark_settings'] = $this->getHelper('Data')->jsonEncode($watermarkSettings);

        // ---------------------------------------

        return $data;
    }

    //########################################

    public function getDefaultData()
    {
        return [

            'title_mode' => Description::TITLE_MODE_PRODUCT,
            'title_template' => '',

            'subtitle_mode' => Description::SUBTITLE_MODE_NONE,
            'subtitle_template' => '',

            'description_mode' => '',
            'description_template' => '',

            'condition_mode' => Description::CONDITION_MODE_EBAY,
            'condition_value' => Description::CONDITION_EBAY_NEW,
            'condition_attribute' => '',

            'condition_note_mode' => Description::CONDITION_NOTE_MODE_NONE,
            'condition_note_template' => '',

            'product_details' => $this->getHelper('Data')->jsonEncode(
                [
                    'isbn'  => ['mode' => Description::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''],
                    'epid'  => ['mode' => Description::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''],
                    'upc'   => ['mode' => Description::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''],
                    'ean'   => ['mode' => Description::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''],
                    'brand' => ['mode' => Description::PRODUCT_DETAILS_MODE_NONE, 'attribute' => ''],
                    'mpn'   => ['mode' => Description::PRODUCT_DETAILS_MODE_DOES_NOT_APPLY, 'attribute' => ''],
                    'include_ebay_details' => 1,
                    'include_image'        => 1,
                ]
            ),

            'editor_type' => Description::EDITOR_TYPE_SIMPLE,
            'cut_long_titles' => Description::CUT_LONG_TITLE_ENABLED,
            'hit_counter' => Description::HIT_COUNTER_NONE,

            'enhancement' => '',
            'gallery_type' => Description::GALLERY_TYPE_EMPTY,

            'image_main_mode' => Description::IMAGE_MAIN_MODE_PRODUCT,
            'image_main_attribute' => '',
            'gallery_images_mode' => Description::GALLERY_IMAGES_MODE_NONE,
            'gallery_images_limit' => 0,
            'gallery_images_attribute' => '',
            'variation_images_mode' => Description::VARIATION_IMAGES_MODE_PRODUCT,
            'variation_images_limit' => 1,
            'variation_images_attribute' => '',
            'default_image_url' => '',

            'variation_configurable_images' => $this->getHelper('Data')->jsonEncode([]),
            'use_supersize_images' => Description::USE_SUPERSIZE_IMAGES_NO,

            'watermark_mode' => Description::WATERMARK_MODE_NO,

            'watermark_settings' => $this->getHelper('Data')->jsonEncode(
                [
                    'position' => Description::WATERMARK_POSITION_TOP,
                    'scale' => Description::WATERMARK_SCALE_MODE_NONE,
                    'transparent' => Description::WATERMARK_TRANSPARENT_MODE_NO,

                    'hashes' => [
                        'current'  => '',
                        'previous' => '',
                    ]
                ]
            ),

            'watermark_image' => null
        ];
    }

    //########################################
}

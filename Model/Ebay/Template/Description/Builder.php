<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Ebay\Template\Description;

class Builder extends \Ess\M2ePro\Model\Ebay\Template\Builder\AbstractModel
{
    //########################################

    public function build(array $data)
    {
        if (empty($data)) {
            return NULL;
        }

        $this->validate($data);

        $data = $this->prepareData($data);

        $template = $this->ebayFactory->getObject('Template\Description');

        if (isset($data['id'])) {
            $template->load($data['id']);
            $template->addData($data);
            $template->getChildObject()->addData($data);
        } else {
            $template->setData($data);
        }

        $template->save();

        return $template;
    }

    //########################################

    protected function prepareData(array &$data)
    {
        $prepared = parent::prepareData($data);

        $defaultData = $this->activeRecordFactory->getObject('Ebay\Template\Description')->getDefaultSettings();

        $defaultData['enhancement'] = explode(',', $defaultData['enhancement']);
        $defaultData['product_details'] = json_decode($defaultData['product_details'], true);
//        $defaultData['watermark_settings'] = json_decode($defaultData['watermark_settings'], true);

        $data = $this->getHelper('Data')->arrayReplaceRecursive($defaultData, $data);

        if (isset($data['title_mode'])) {
            $prepared['title_mode'] = (int)$data['title_mode'];
        }

        if (isset($data['title_template'])) {
            $prepared['title_template'] = $data['title_template'];
        }

        if (isset($data['subtitle_mode'])) {
            $prepared['subtitle_mode'] = (int)$data['subtitle_mode'];
        }

        if (isset($data['subtitle_template'])) {
            $prepared['subtitle_template'] = $data['subtitle_template'];
        }

        if (isset($data['description_mode'])) {
            $prepared['description_mode'] = (int)$data['description_mode'];
        }

        if (isset($data['description_template'])) {
            $prepared['description_template'] = $data['description_template'];
        }

        if (isset($data['condition_mode'])) {
            $prepared['condition_mode'] = (int)$data['condition_mode'];
        }

        if (isset($data['condition_value'])) {
            $prepared['condition_value'] = (int)$data['condition_value'];
        }

        if (isset($data['condition_attribute'])) {
            $prepared['condition_attribute'] = $data['condition_attribute'];
        }

        if (isset($data['condition_note_mode'])) {
            $prepared['condition_note_mode'] = (int)$data['condition_note_mode'];
        }

        if (isset($data['condition_note_template'])) {
            $prepared['condition_note_template'] = $data['condition_note_template'];
        }

        if (isset($data['product_details'])) {

            $prepared['product_details'] = $data['product_details'];

            if (is_array($prepared['product_details'])) {
                $prepared['product_details'] = json_encode($prepared['product_details']);
            }
        }

        if (isset($data['editor_type'])) {
            $prepared['editor_type'] = (int)$data['editor_type'];
        }

        if (isset($data['cut_long_titles'])) {
            $prepared['cut_long_titles'] = (int)$data['cut_long_titles'];
        }

        if (isset($data['hit_counter'])) {
            $prepared['hit_counter'] = $data['hit_counter'];
        }

        if (isset($data['enhancement'])) {

            $prepared['enhancement'] = $data['enhancement'];

            if (is_array($prepared['enhancement'])) {
                $prepared['enhancement'] = implode(',', $data['enhancement']);
            }
        }

        if (isset($data['gallery_type'])) {
            $prepared['gallery_type'] = (int)$data['gallery_type'];
        }

        if (isset($data['image_main_mode'])) {
            $prepared['image_main_mode'] = (int)$data['image_main_mode'];
        }

        if (isset($data['image_main_attribute'])) {
            $prepared['image_main_attribute'] = $data['image_main_attribute'];
        }

        if (isset($data['gallery_images_mode'])) {
            $prepared['gallery_images_mode'] = (int)$data['gallery_images_mode'];
        }

        if (isset($data['gallery_images_limit'])) {
            $prepared['gallery_images_limit'] = (int)$data['gallery_images_limit'];
        }

        if (isset($data['gallery_images_attribute'])) {
            $prepared['gallery_images_attribute'] = $data['gallery_images_attribute'];
        }

        if (isset($data['variation_images_mode'])) {
            $prepared['variation_images_mode'] = (int)$data['variation_images_mode'];
        }

        if (isset($data['variation_images_limit'])) {
            $prepared['variation_images_limit'] = (int)$data['variation_images_limit'];
        }

        if (isset($data['variation_images_attribute'])) {
            $prepared['variation_images_attribute'] = $data['variation_images_attribute'];
        }

        if (isset($data['reserve_price_custom_attribute'])) {
            $prepared['reserve_price_custom_attribute'] = $data['reserve_price_custom_attribute'];
        }

        if (isset($data['default_image_url'])) {
            $prepared['default_image_url'] = $data['default_image_url'];
        }

        if (isset($data['variation_configurable_images'])) {
            $prepared['variation_configurable_images'] = $data['variation_configurable_images'];

            if (is_array($prepared['variation_configurable_images'])) {
                $prepared['variation_configurable_images'] = json_encode($prepared['variation_configurable_images']);
            }
        }

        if (isset($data['use_supersize_images'])) {
            $prepared['use_supersize_images'] = (int)$data['use_supersize_images'];
        }

//        if (isset($data['watermark_mode'])) {
//            $prepared['watermark_mode'] = (int)$data['watermark_mode'];
//        }

        // ---------------------------------------

//        $watermarkSettings = array();
//        $hashChange = false;
//
//        if (isset($data['watermark_settings']['position'])) {
//            $watermarkSettings['position'] = (int)$data['watermark_settings']['position'];
//
//            if (isset($data['old_watermark_settings']) &&
//                $data['watermark_settings']['position'] != $data['old_watermark_settings']['position']) {
//                $hashChange = true;
//            }
//        }
//
//        if (isset($data['watermark_settings']['scale'])) {
//            $watermarkSettings['scale'] = (int)$data['watermark_settings']['scale'];
//
//            if (isset($data['old_watermark_settings']) &&
//                $data['watermark_settings']['scale'] != $data['old_watermark_settings']['scale']) {
//                $hashChange = true;
//            }
//        }
//
//        if (isset($data['watermark_settings']['transparent'])) {
//            $watermarkSettings['transparent'] = (int)$data['watermark_settings']['transparent'];
//
//            if (isset($data['old_watermark_settings']) &&
//                $data['watermark_settings']['transparent'] != $data['old_watermark_settings']['transparent']) {
//                $hashChange = true;
//            }
//        }

        // ---------------------------------------

//        if (!empty($_FILES['watermark_image']['tmp_name'])) {
//
//            $hashChange = true;
//
//            $prepared['watermark_image'] = file_get_contents($_FILES['watermark_image']['tmp_name']);
//
//            if (isset($prepared['id'])) {
//
//                $varDir = $this->modelFactory->getObject(
//                    'VariablesDir',
//                    array('child_folder' => 'ebay/template/description/watermarks')
//                );
//
//                $watermarkPath = $varDir->getPath().(int)$prepared['id'].'.png';
//                if (is_file($watermarkPath)) {
//                    @unlink($watermarkPath);
//                }
//            }
//
//        } elseif (!empty($data['old_watermark_image']) && !isset($prepared['id'])) {
//            $prepared['watermark_image'] = base64_decode($data['old_watermark_image']);
//        }

        // ---------------------------------------

//        if ($hashChange) {
//            $watermarkSettings['hashes']['previous'] = $data['old_watermark_settings']['hashes']['current'];
//            $watermarkSettings['hashes']['current'] = substr(sha1(microtime()), 0, 5);
//        } else {
//            $watermarkSettings['hashes']['previous'] = $data['old_watermark_settings']['hashes']['previous'];
//            $watermarkSettings['hashes']['current'] = $data['old_watermark_settings']['hashes']['current'];
//        }
//
//        $prepared['watermark_settings'] = json_encode($watermarkSettings);

        // ---------------------------------------

        return $prepared;
    }

    //########################################
}
<?php

namespace Ess\M2ePro\Controller\Adminhtml\Amazon\Template\SellingFormat;

use Ess\M2ePro\Controller\Adminhtml\Amazon\Template;
use Ess\M2ePro\Helper\Component\Amazon;

class Save extends Template
{
    protected $dateTime;

    //########################################

    public function __construct(
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Ess\M2ePro\Model\ActiveRecord\Component\Parent\Amazon\Factory $amazonFactory,
        \Ess\M2ePro\Controller\Adminhtml\Context $context
    )
    {
        $this->dateTime = $dateTime;
        parent::__construct($amazonFactory, $context);
    }

    //########################################

    public function execute()
    {
        $post = $this->getRequest()->getPost();

        if (!$post->count()) {
            $this->_forward('index');
            return;
        }

        $id = $this->getRequest()->getParam('id');

        // Base prepare
        // ---------------------------------------
        $data = array();

        $keys = array(
            'title',

            'qty_mode',
            'qty_custom_value',
            'qty_custom_attribute',
            'qty_percentage',
            'qty_modification_mode',
            'qty_min_posted_value',
            'qty_max_posted_value',

            'price_mode',
            'price_coefficient',
            'price_custom_attribute',

            'map_price_mode',
            'map_price_custom_attribute',

            'sale_price_mode',
            'sale_price_coefficient',
            'sale_price_custom_attribute',

            'price_variation_mode',

            'sale_price_start_date_mode',
            'sale_price_end_date_mode',

            'sale_price_start_date_value',
            'sale_price_end_date_value',

            'sale_price_start_date_custom_attribute',
            'sale_price_end_date_custom_attribute',

            'price_vat_percent'
        );

        foreach ($keys as $key) {
            if (isset($post[$key])) {
                $data[$key] = $post[$key];
            }
        }

        if ($data['sale_price_start_date_value'] === '') {
            $data['sale_price_start_date_value'] = $this->getHelper('Data')->getCurrentGmtDate(
                false, 'Y-m-d 00:00:00'
            );
        } else {
            $data['sale_price_start_date_value'] = $this->dateTime->formatDate(
                $data['sale_price_start_date_value'],
                true
            );
        }
        if ($data['sale_price_end_date_value'] === '') {
            $data['sale_price_end_date_value'] = $this->getHelper('Data')->getCurrentGmtDate(
                false, 'Y-m-d 00:00:00'
            );
        } else {
            $data['sale_price_end_date_value'] = $this->dateTime->formatDate(
                $data['sale_price_end_date_value'],
                true
            );
        }

        $data['title'] = strip_tags($data['title']);
        // ---------------------------------------

        // Add or update model
        // ---------------------------------------
        $model = $this->amazonFactory->getObject('Template\SellingFormat');

        $oldData = [];

        if ($id) {
            $model->load($id);

            $oldData = array_merge(
                $model->getDataSnapshot(),
                $model->getChildObject()->getDataSnapshot()
            );
        }

        $model->addData($data)->save();
        $model->getChildObject()->addData(array_merge(
            [$model->getResource()->getChildPrimary(Amazon::NICK) => $model->getId()],
            $data
        ));
        $model->save();

        $newData = array_merge($model->getDataSnapshot(), $model->getChildObject()->getDataSnapshot());
        $model->getChildObject()->setSynchStatusNeed($newData, $oldData);

        if ($this->isAjax()) {
            $this->setJsonContent([
                'status' => true
            ]);
            return $this->getResult();
        }

        $id = $model->getId();

        $this->messageManager->addSuccess($this->__('Policy was successfully saved'));
        return $this->_redirect($this->getHelper('Data')->getBackUrl('*/amazon_template/index', array(), array(
            'edit' => array(
                'id' => $id,
                'wizard' => $this->getRequest()->getParam('wizard'),
                'close_on_save' => $this->getRequest()->getParam('close_on_save')
            ),
        )));
    }

    //########################################
}
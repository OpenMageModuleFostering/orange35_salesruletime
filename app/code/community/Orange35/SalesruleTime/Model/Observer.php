<?php
class Orange35_SalesruleTime_Model_Observer{

    const DOWNLOAD_URL = 'https://orange35.com/download/core';
    const MODULE_FULL_NAME = 'Time Dependent Shopping Cart Price Rules';

    public function adminhtmlPromoQuoteEditTabMainPrepareForm($observer){
        $form = $observer->getData("form");
        $fs = $form->getElement("base_fieldset");
        $dateFormatIso = Mage::app()->getLocale()->getDateTimeFormat(Mage_Core_Model_Locale::FORMAT_TYPE_SHORT);
        foreach($fs->getElements() as $element){
            if($element->getName() == "to_date" || $element->getName() == "from_date"){
                $element->setData("input_format", Varien_Date::DATETIME_INTERNAL_FORMAT);
                $element->setData("format", $dateFormatIso);
                $element->setData("time", true);
            }
        }
        $model = Mage::registry('current_promo_quote_rule');
        $form->setValues($model->getData());
    }

    public function checkRequiredModules()
    {
        return $this;
        if (Mage::getSingleton('admin/session')->isLoggedIn()) {
            $coreConfig = Mage::getConfig()->getModuleConfig('Orange35_Core');
            if (empty($coreConfig) || !$coreConfig->is('active', 'true')) {
                $notificationModel = Mage::getModel('adminnotification/inbox');
                $title = self::MODULE_FULL_NAME. ' Installation Error.';
                $notification = $notificationModel->getCollection()->addFieldToFilter('title', $title)
                    ->getFirstItem();
                $notificationId = $notification->getData('notification_id');
                if ($notificationId == null) {
                    $description = 'Important: Please setup Orange35 Core in order to finish <strong>'. self::MODULE_FULL_NAME .'</strong> installation.<br />
       					Please download <a href="' . self::DOWNLOAD_URL . '" target="_blank">Orange35 Core</a> and setup it via Magento Connect.<br />
       					Please refer to installation guide';
                    $data = array(
                        'severity'    => '4',
                        'date_added'  => date('Y-m-d H:i:s'),
                        'title'       => $title,
                        'description' => $description,
                        'is_read'     => '0',
                        'is_remove'   => '0'
                    );
                    $notificationModel->setData($data);
                    $notificationModel->save();
                }
            }
        }
    }
}
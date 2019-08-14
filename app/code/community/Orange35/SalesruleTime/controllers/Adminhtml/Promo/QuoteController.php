<?php
require_once Mage::getModuleDir('controllers', 'Mage_Adminhtml') . DS . 'Promo/QuoteController.php';
class Orange35_SalesruleTime_Adminhtml_Promo_QuoteController extends Mage_Adminhtml_Promo_QuoteController {

    public function generateAction()
    {
        if (!$this->getRequest()->isAjax()) {
            $this->_forward('noRoute');
            return;
        }
        $result = array();
        $this->_initRule();

        /** @var $rule Mage_SalesRule_Model_Rule */
        $rule = Mage::registry('current_promo_quote_rule');

        if (!$rule->getId()) {
            $result['error'] = Mage::helper('salesrule')->__('Rule is not defined');
        } else {
            try {
                $data = $this->getRequest()->getParams();
                if (!empty($data['to_date'])) {
                    $data = array_merge($data, $this->_filterDateTime($data, array('to_date')));
                }

                /** @var $generator Mage_SalesRule_Model_Coupon_Massgenerator */
                $generator = $rule->getCouponMassGenerator();
                if (!$generator->validateData($data)) {
                    $result['error'] = Mage::helper('salesrule')->__('Not valid data provided');
                } else {
                    $generator->setData($data);
                    $generator->generatePool();
                    $generated = $generator->getGeneratedCount();
                    $this->_getSession()->addSuccess(Mage::helper('salesrule')->__('%s Coupon(s) have been generated', $generated));
                    $this->_initLayoutMessages('adminhtml/session');
                    $result['messages']  = $this->getLayout()->getMessagesBlock()->getGroupedHtml();
                }
            } catch (Mage_Core_Exception $e) {
                $result['error'] = $e->getMessage();
            } catch (Exception $e) {
                $result['error'] = Mage::helper('salesrule')->__('An error occurred while generating coupons. Please review the log and try again.');
                Mage::logException($e);
            }
        }
        $this->getResponse()->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            try {
                /** @var $model Mage_SalesRule_Model_Rule */
                $model = Mage::getModel('orange35_salesruletime/salesRule_rule');
                Mage::dispatchEvent(
                    'adminhtml_controller_salesrule_prepare_save',
                    array('request' => $this->getRequest()));
                $data = $this->getRequest()->getPost();
                $data = $this->_filterDateTime($data, array('from_date', 'to_date'));
                $id = $this->getRequest()->getParam('rule_id');
                if ($id) {
                    $model->load($id);
                    if ($id != $model->getId()) {
                        Mage::throwException(Mage::helper('salesrule')->__('Wrong rule specified.'));
                    }
                }

                $session = Mage::getSingleton('adminhtml/session');

                $validateResult = $model->validateData(new Varien_Object($data));
                if ($validateResult !== true) {
                    foreach($validateResult as $errorMessage) {
                        $session->addError($errorMessage);
                    }
                    $session->setPageData($data);
                    $this->_redirect('*/*/edit', array('id'=>$model->getId()));
                    return;
                }

                if (isset($data['simple_action']) && $data['simple_action'] == 'by_percent'
                    && isset($data['discount_amount'])) {
                    $data['discount_amount'] = min(100,$data['discount_amount']);
                }
                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }
                if (isset($data['rule']['actions'])) {
                    $data['actions'] = $data['rule']['actions'];
                }
                unset($data['rule']);
                $model->loadPost($data);

                $useAutoGeneration = (int)!empty($data['use_auto_generation']);
                $model->setUseAutoGeneration($useAutoGeneration);

                $session->setPageData($model->getData());

                $model->save();
                $session->addSuccess(Mage::helper('salesrule')->__('The rule has been saved.'));
                $session->setPageData(false);
                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $model->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Mage_Core_Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('rule_id');
                if (!empty($id)) {
                    $this->_redirect('*/*/edit', array('id' => $id));
                } else {
                    $this->_redirect('*/*/new');
                }
                return;

            } catch (Exception $e) {
                $this->_getSession()->addError(
                    Mage::helper('catalogrule')->__('An error occurred while saving the rule data. Please review the log and try again.'));
                Mage::logException($e);
                Mage::getSingleton('adminhtml/session')->setPageData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('rule_id')));
                return;
            }
        }
        $this->_redirect('*/*/');
    }
}
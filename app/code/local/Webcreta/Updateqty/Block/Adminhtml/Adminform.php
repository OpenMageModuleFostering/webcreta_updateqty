<?php
class Webcreta_Updateqty_Block_Adminhtml_Adminform extends Mage_Adminhtml_Block_Template {  
	public function __construct() {  
		parent::__construct();  
		$this->setTemplate('webcreta/updateqty.phtml');  
		$this->setFormAction(Mage::getUrl('*/*/new'));  
	  }  
}  
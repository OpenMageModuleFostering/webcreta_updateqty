<?php
class Webcreta_Updateqty_Block_Adminhtml_Updateqty extends Mage_Core_Block_Template{

	 public function __construct()
	  {
		  parent::__construct();
		  $this->setTemplate('webcreta/updateqty.phtml');
		  $this->setFormAction(Mage::getUrl('*/*/upload'));  
		 // return $this;
	  }

}
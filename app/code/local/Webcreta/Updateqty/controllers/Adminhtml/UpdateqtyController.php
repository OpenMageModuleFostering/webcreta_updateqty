<?php
	class Webcreta_Updateqty_Adminhtml_UpdateqtyController extends Mage_Adminhtml_Controller_Action
	{
		protected function _initAction()
		{
			$this->loadLayout()->_setActiveMenu("updateqty")->_addBreadcrumb(Mage::helper("updateqty")->__("Updatestock  Manager"),Mage::helper("updateqty")->__("Updatestock Manager"));
			return $this;
		}
		public function indexAction() {
			$this->_title($this->__("Update Mass Qty"));
			$this->_initAction();		
			
			$block_strt = $this->getLayout()
			->createBlock('core/text', 'example-block')
			->setText("
			<h1>Product Quantity Import/update</h1>
			<p>If you dont have the CSV already, click on to the follwoing button for the export</p>
			");
			$this->_addContent($block_strt);
			
			$url_export = $this->getUrl('*/*/exportcsvmy');
			
			$block_export = $this->getLayout()
			->createBlock('adminhtml/widget_button')
			->setData(array(
			'label'     => Mage::helper('updateqty')->__('Export CSV'),
			'onclick'   => 'setLocation(\'' . $url_export .'\')',
			'class'     => 'import',
			));
			$this->_addContent($block_export);
			
			$block_mid_exprot = $this->getLayout()
			->createBlock('core/text', 'example-block5')
			->setText("<p></p>
			<p>Use following uploader to import csv file..!</p>
			");
			$this->_addContent($block_mid_exprot);
			$this->_addContent($this->getLayout()->createBlock('updateqty/adminhtml_updateqty'));	
			
			$block_mid = $this->getLayout()
			->createBlock('core/text', 'example-block2')
			->setText("<p></p>
			<p>Once done with the import click onto the follwoing button to Update the products</p>
			");
			$this->_addContent($block_mid);
			
			$uploader = $this->getLayout()->createBlock('adminhtml/media_uploader');
			$url = $this->getUrl('*/*/importcsvmy');
			
			$block = $this->getLayout()
			->createBlock('adminhtml/widget_button')
			->setData(array(
			'label'     => Mage::helper('updateqty')->__('Start Update'),
			'onclick'   => 'setLocation(\'' . $url .'\')',
			'class'     => 'save',
			));
			$this->_addContent($block);			
			
			$this->renderLayout();
		}
		
		public function uploadAction() {  
			$post_data=$this->getRequest()->getPost(); 
			if ($post_data) {
				try {
					if(isset($_FILES['name']['name']) and (file_exists($_FILES['name']['tmp_name']))) {
						try {
							$uploader = new Varien_File_Uploader('name');
							$uploader->setAllowedExtensions(array('csv')); 
							$uploader->setAllowCreateFolders(true); //for creating the directory if not exists
							$uploader->setAllowRenameFiles(false); 
							$uploader->setFilesDispersion(false);
							$path = Mage::getBaseDir().DS.'import'.DS;	
							if($_FILES['name']['name'])	{array_map('unlink', glob($path.DS.'*')); }   
							$uploader->save($path, $_FILES['name']['name']);
							Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("File's been imported successfully!"));
						}
						catch(Exception $e) {
							Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
							Mage::getSingleton("adminhtml/session")->setUpdatestockData($this->getRequest()->getPost());return;
						}
					}
				} 
				catch (Exception $e) {
					Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
					Mage::getSingleton("adminhtml/session")->setUpdatestockData($this->getRequest()->getPost());
					return;
				}
				
			}
			$this->_redirect("*/*/");
		}
		
		public function importcsvmyAction()
		{
			
			$csv_folder     = Mage::getBaseDir().DS.'import'.DS;
			$dir = $csv_folder;
			$dh = opendir($dir);				
			while (($file2 = readdir($dh)) !== false) {
				if ($file2 == '.' or $file2 == '..') continue;
				$file_name_2=$file2;
			}
			closedir($dh);
			echo $CSVFileName    = $csv_folder.$file_name_2;		
			umask(0);
			Mage::app()->setCurrentStore(Mage_Core_Model_App::ADMIN_STORE_ID);
			
			$csv = new Varien_File_Csv();
			$data = $csv->getData($CSVFileName);
	
			array_shift($data);
			$message = '';
			$count   = 1;
			
			foreach($data as $_data){
				if($this->_checkIfSkuExists($_data[0])){
					try{
						$this->_updateStocks($_data);
						
						}catch(Exception $e){
						$message .=  $count .'> Error:: while Upating  Qty (' . $_data[1] . ') of Sku (' . $_data[0] . ') => '.$e->getMessage().'<br />';
					}
					}else{
					$message .=  $count .'> Error:: Product with Sku (' . $_data[0] . ') does\'t exist.<br />';
				}
				$count++;
			}
					Mage::getSingleton('adminhtml/session')->addSuccess("Quantity updated!");
					if(!empty($message)){
						Mage::getSingleton('adminhtml/session')->addNotice($message);
					}
					
			
			$this->_redirect('*/*/');
			}	
			public function exportcsvmyAction()
			{
				$csv_folder     = Mage::getBaseDir().DS.'import'.DS;
				$CSVFileName    = $csv_folder.'/'.'qty_update.csv';
				$fp = fopen($CSVFileName, "w");
				$line= "sku,qty\n";
				fputs($fp, $line);
				try {
					$line = "";
					foreach(Mage::getModel('catalog/product')->getCollection() as $product)
					{
						$productId=$product->getId();
						$product_qty=(int) Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId)->getQty(); 
						$product = Mage::getSingleton('catalog/product')->load($productId);
						$line .= "\"{$product->getSku()}\",\"{$product_qty}\"\n";																	
					}
					fputs($fp, $line);
					fclose($fp);
					if (! is_file ( $CSVFileName ) || ! is_readable ( $CSVFileName )) {
						throw new Exception ( );
					}
					
					$this->getResponse ()
					->setHttpResponseCode ( 200 )
					->setHeader ( 'Pragma', 'public', true )
					->setHeader ( 'Cache-Control', 'must-revalidate, post-check=0, pre-check=0', true )
					->setHeader ( 'Content-type', 'application/force-download' )
					->setHeader ( 'Content-Length', filesize($CSVFileName) )
					->setHeader ('Content-Disposition', 'inline' . '; filename=' . basename($CSVFileName) );
					$this->getResponse ()->clearBody ();
					$this->getResponse ()->sendHeaders ();
					readfile ( $CSVFileName );	
					exit();			 
					
				}
				catch (Exception $e) {
					Mage::getSingleton("adminhtml/session")->addError($e->getMessage());
				}
				$this->_redirect('*/*/');
			}
			public function openmyAction()
			{
				$post_data=$this->getRequest()->getPost();
				print_r($post_data);
				exit();
				Mage::getSingleton("adminhtml/session")->addSuccess(Mage::helper("adminhtml")->__("File's been imported successfully!"));
				$this->_redirect("*/*/");
			}
			
			public function _getConnection($type = 'core_read'){
				return Mage::getSingleton('core/resource')->getConnection($type);
			}
			
			public function _getTableName($tableName){
				return Mage::getSingleton('core/resource')->getTableName($tableName);
			}
			
			public function _getAttributeId($attribute_code = 'price'){
				$connection = $this->_getConnection('core_read');
				$sql = "SELECT attribute_id
				FROM " . $this->_getTableName('eav_attribute') . "
				WHERE
				entity_type_id = ?
				AND attribute_code = ?";
				$entity_type_id = $this->_getEntityTypeId();
				return $connection->fetchOne($sql, array($entity_type_id, $attribute_code));
			}
			
			public function _getEntityTypeId($entity_type_code = 'catalog_product'){
				$connection = $this->_getConnection('core_read');
				$sql        = "SELECT entity_type_id FROM " . $this->_getTableName('eav_entity_type') . " WHERE entity_type_code = ?";
				return $connection->fetchOne($sql, array($entity_type_code));
			}
			
			
			
			public function _getIdFromSku($sku){
				$connection = $this->_getConnection('core_read');
				$sql        = "SELECT entity_id FROM " . $this->_getTableName('catalog_product_entity') . " WHERE sku = ?";
				return $connection->fetchOne($sql, array($sku));
			}
			
			public function _updateStocks($data){
				$connection     = $this->_getConnection('core_write');
				$sku            = $data[0];
				$newQty         = $data[1];
				$productId      = $this->_getIdFromSku($sku);
				$attributeId    = $this->_getAttributeId();
				
				$sql            = "UPDATE " . $this->_getTableName('cataloginventory_stock_item') . " csi,
				" . $this->_getTableName('cataloginventory_stock_status') . " css
				SET
				csi.qty = ?,
				csi.is_in_stock = ?,
				css.qty = ?,
				css.stock_status = ?
				WHERE
				csi.product_id = ?
				AND csi.product_id = css.product_id";
				$isInStock      = $newQty > 0 ? 1 : 0;
				$stockStatus    = $newQty > 0 ? 1 : 0;
				$connection->query($sql, array($newQty, $isInStock, $newQty, $stockStatus, $productId));
			}
			
			public function _checkIfSkuExists($sku){
				$connection = $this->_getConnection('core_read');
				$sql        = "SELECT COUNT(*) AS count_no FROM " . $this->_getTableName('catalog_product_entity') . " WHERE sku = ?";
				$count      = $connection->fetchOne($sql, array($sku));//echo $count;die;
				if($count > 0){
					return true;
				}else{
					return false;
				}
			}
		}
?>
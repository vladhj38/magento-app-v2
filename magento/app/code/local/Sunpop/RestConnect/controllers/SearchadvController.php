<?php
/**
 * Catalog Search Controller
 */
class Sunpop_RestConnect_SearchadvController extends Mage_Core_Controller_Front_Action {
	protected function _getSession() {
		return Mage::getSingleton ( 'catalog/session' );
	}

	public function indexAction() {
		//http://domainname/restconnect/searchadv/index/name/aaa/description/bbbb/short_description/ccc/sku/123/price/2to6/tax_class_id/1,2,3
		//		/pagesize/6/
		//?color=5  5 是 option_id
		//'name' => string 'name' (length=4)
		//'description' => string 'des' (length=3)
		//'short_description' => string 'sdes' (length=4)
		//'sku' => string 'sku' (length=3)
		//'price' =>
		//	array (size=2)
		//	'from' => string '0' (length=1)
		//	'to' => string '2' (length=1)
		//'tax_class_id' =>
		//	array (size=1)
		//	0 => string '0' (length=1)
		//'color' =>
		//	array (size=1)
		//	0 => string '5' (length=1)
		$order = ($this->getRequest ()->getParam ( 'order' )) ? ($this->getRequest ()->getParam ( 'order' )) : 'entity_id';
		$dir = ($this->getRequest ()->getParam ( 'dir' )) ? ($this->getRequest ()->getParam ( 'dir' )) : 'desc';
		$page = ($this->getRequest ()->getParam ( 'page' )) ? ($this->getRequest ()->getParam ( 'page' )) : 1;
		$limit = ($this->getRequest ()->getParam ( 'limit' )) ? ($this->getRequest ()->getParam ( 'limit' )) : 5;
	
		$farray = array();//构建一个addFilters 数组参数
		if($this->getRequest ()->getParam ( 'name' )) $farray['name'] = $this->getRequest ()->getParam ( 'name' );
		if($this->getRequest ()->getParam ( 'description' )) $farray['description'] = $this->getRequest ()->getParam ( 'description' );
		if($this->getRequest ()->getParam ( 'short_description' )) $farray['short_description'] = $this->getRequest ()->getParam ( 'short_description' );
		if($this->getRequest ()->getParam ( 'sku' )) $farray['sku'] = $this->getRequest ()->getParam ( 'short_description' );
		if($this->getRequest ()->getParam ( 'price' )) {
			$price = explode("to", $this->getRequest ()->getParam ( 'price' ));
			$from = $price[1] ? $price[0] : '0';
			$to = !$price[1] ? $price[0] : $price[1];
			$farray['price'] = array(
					'from' => $from,
					'to'   => $to
			);
		}
		if(!empty($this->getRequest ()->getParam ( 'tax_class_id' )) || $this->getRequest ()->getParam ( 'tax_class_id' ) == '0') {				
			$farray['tax_class_id'] = explode(",", $this->getRequest ()->getParam ( 'tax_class_id' ));
		}
		foreach ($this->getRequest ()->getParams() as $key => $value){
			if(!in_array($key, array('name','description','short_description','sku','price','tax_class_id'))){
				if(!empty($value) || $value == '0') {				
					$farray[$key] = explode(",", $value);
					if(count($farray[$key]) <= 1) $farray[$key] = $value;
				}
			}
		}
		$searcher = Mage::getSingleton ( 'catalogsearch/advanced' )->addFilters ( $farray );

		$result = $searcher->getProductCollection();
		
		//pages
		
		$result->setPageSize($limit);
		
		$result->setCurPage($page);

		
		//sort
		$result->addAttributeToSort($order,$dir);

		
		$result->load();
		
		$lastpagenumber = $result->getLastPageNumber();
		
		
		
		$baseCurrency = Mage::app ()->getStore ()->getBaseCurrency ()->getCode ();
	    $currentCurrency = Mage::app ()->getStore ()->getCurrentCurrencyCode ();
		foreach($result as $product){
		    $product = Mage::getModel ( 'catalog/product' )->load (  $product->getId () );
		    $productlist [] = array (
        		'entity_id' => $product->getId (),
        		'sku' => $product->getSku (),
        		'name' => $product->getName (),
        		'news_from_date' => $product->getNewsFromDate (),
        		'news_to_date' => $product->getNewsToDate (),
        		'special_from_date' => $product->getSpecialFromDate (),
        		'special_to_date' => $product->getSpecialToDate (),
        		'image_url' => $product->getImageUrl (),
        		'url_key' => $product->getProductUrl (),
        		'regular_price_with_tax' => number_format ( Mage::helper ( 'directory' )->currencyConvert ( $product->getPrice (), $baseCurrency, $currentCurrency ), 2, '.', '' ),
        		'final_price_with_tax' => number_format ( Mage::helper ( 'directory' )->currencyConvert ( $product->getSpecialPrice (), $baseCurrency, $currentCurrency ), 2, '.', '' ),
        		'symbol' => Mage::app ()->getLocale ()->currency ( Mage::app ()->getStore ()->getCurrentCurrencyCode () )->getSymbol ()
    		);
		}
		$returndata['productlist'] = $productlist;
		$returndata['lastpagenumber'] = $lastpagenumber;
		echo Mage::helper('core')->jsonEncode($returndata);
	}

}

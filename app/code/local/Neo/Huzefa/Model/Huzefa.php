<?php

class Neo_Huzefa_Model_Huzefa extends Mage_Core_Model_Abstract
{
    protected function _construct()
    {
        $this->_init('huzefa/huzefa');
    }

    public function addProduct($sku,$type="simple") {
      //script to add a simple product in magento
       
        /** @var $product Mage_Catalog_Model_Product */
        $newProduct =Mage::getModel('catalog/product');
    $newProduct->setAttributeSetId(4)
               ->setTypeId('simple')
               ->setVisibility(Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH)
               ->setTaxClassId(2)
               ->setCreatedAt(strtotime('now'))
               ->setName('test')
               ->setSku('test')
               ->setWeight(1)
               ->setStatus(1)
               ->setPrice(1000)
               ->setCategoryIds(explode(',',array('2','3'))
               ->setWebsiteIds(1)
               ->setDescription('hello')
               ->setShortDescription('hello')

               ->setStockData(array(
                                     'manage_stock'=>0,
                                     'min_sale_qty'=>$data[22],
                                     'max_sale_qty'=>$data[23]))
    $newProduct->save();                
}catch(Exception $e){
     $result['status'] = 3;
     $result['message'] = 'There is an ERROR happened! NOT ALL products are created! Error:'.$e->getMessage();
     echo json_encode($result);
     return;

        return $newProduct->getId();
    }

public function addConfigurable() {


}

public function addDownloadable() {
}

public function addGroup() {
}

public function addBundle() {
}

public function addVirtual() {}

public function addSimpleToCart() {}

public function addConfigToCart() {}
public function addGroupToCart() {}
public function addBundleToCart() {}
public function addDownloadableToCart() {}

}

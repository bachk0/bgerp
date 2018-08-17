<?php


/**
 * Модел за "Детайл на зоните"
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_ZoneDetails extends core_Detail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайл на зоните';
    
    
    /**
     * Кой може да листва?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'zoneId';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'productId, packagingId, documentQuantity, movementQuantity';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('zoneId', 'key(mvc=rack_Zones)', 'caption=Зона, input=hidden,silent,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,tdClass=productCell leftCol wrap');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack|displayPrice');
        $this->FLD('documentQuantity', 'double', 'caption=Документи,mandatory');
        $this->FLD('movementQuantity', 'double', 'caption=Движения,mandatory');
        
        $this->setDbUnique('zoneId,productId,packagingId');
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (is_object($rec)) {
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            
            if(isset($rec->movementQuantity)){
                $rec->movementQuantity = $rec->movementQuantity / $rec->quantityInPack;
            }
            
            if(isset($rec->documentQuantity)){
                $rec->documentQuantity = $rec->documentQuantity / $rec->quantityInPack;
            }
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        $row->ROW_ATTR['class'] = 'row-added';
    }
    
    
    /**
     * Записва движение в зоната
     * 
     * @param int $zoneId      - ид на зона
     * @param int $productId   - ид на артикул
     * @param int $packagingId - ид на опаковка
     * @param double $quantity - количество в основна мярка
     * 
     * @return void
     */
    public static function recordMovement($zoneId, $productId, $packagingId, $quantity)
    {
        $newRec = self::fetch("#zoneId = {$zoneId} AND #productId = {$productId} AND #packagingId = {$packagingId}");
        if(empty($newRec)){
            $newRec = (object)array('zoneId' => $zoneId, 'productId' => $productId, 'packagingId' => $packagingId, 'movementQuantity' => 0, 'documentQuantity' => null);
        }
        $newRec->movementQuantity += $quantity;
        
        self::save($newRec);
    }
    
    
    /**
     * Синхронизиране на зоните с документа
     * 
     * @param int $zoneId
     * @param int $containerId
     */
    public static function syncWithDoc($zoneId, $containerId = null)
    {
        if(isset($containerId)){
            $document = doc_Containers::getDocument($containerId);
            $products = $document->getProductsSummary();
            $exRecs = array();
            
            if(is_array($products)){
                foreach ($products as $obj){
                    $newRec = self::fetch("#zoneId = {$zoneId} AND #productId = {$obj->productId} AND #packagingId = {$obj->packagingId}");
                    if(empty($newRec)){
                        $newRec = (object)array('zoneId' => $zoneId, 'productId' => $obj->productId, 'packagingId' => $obj->packagingId, 'movementQuantity' => null, 'documentQuantity' => 0);
                    }
                    $newRec->documentQuantity = $obj->quantity;
                    
                    self::save($newRec);
                    $exRecs[$newRec->id] = $newRec->id;
                }
            }
            
            // Тези които не са се обновили се изтриват
            if(count($exRecs)){
                self::nullifyQuantityFromDocument($zoneId, $exRecs);
            }
        } else {
            self::nullifyQuantityFromDocument($zoneId);
        }
    }
    
    
    /**
     * Зануляване на очакваното количество по документи
     * 
     * @param int $zoneId
     * @param array $notIn
     */
    private static function nullifyQuantityFromDocument(int $zoneId, array $notIn = array())
    {
        $query = self::getQuery();
        $query->where("#zoneId = {$zoneId}");
        $query->where("#documentQuantity IS NOT NULL");
        if(count($notIn)){
            $query->notIn("id", $notIn);
        }
        
        while($rec = $query->fetch()){
            $rec->documentQuantity = null;
            self::save($rec);
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Ако няма никакви количества се изтрива
        if(empty($rec->documentQuantity) && empty($rec->movementQuantity)){
            self::delete($rec->id);
        }
    }
}
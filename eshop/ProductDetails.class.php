<?php



/**
 * Мениджър за детайл в ешоп артикулите
 *
 *
 * @category  bgerp
 * @package   eshop
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_ProductDetails extends core_Detail
{
	
	
	/**
	 * Име на поле от модела, външен ключ към мастър записа
	 */
	public $masterKey = 'eshopProductId';
	
	
	/**
	 * Плъгини за зареждане
	 */
	public $loadList = 'eshop_Wrapper, plg_Created, plg_Modified, plg_SaveAndNew, plg_RowTools2, plg_Select, plg_AlignDecimals2';
	
	
	/**
	 * Единично заглавие
	 */
	public $singleTitle = 'опция';
	
	
	/**
	 * Заглавие
	 */
	public $title = 'Опции на артикулите в онлайн магазина';
	
	
	/**
	 * Кои полета да се показват в листовия изглед
	 */
	public $listFields = 'eshopProductId=Артикул в е-мага,productId,packagingId,packQuantity,createdOn,createdBy,modifiedOn,modifiedBy';
	
	
	/**
	 * Кой има право да променя?
	 */
	public $canEdit = 'eshop,ceo';
	
	
	/**
	 * Кой има право да добавя?
	 */
	public $canAdd = 'eshop,ceo';
	
	
	/**
	 * Кой може да го разглежда?
	 */
	public $canList = 'eshop,ceo';
	
	
	/**
	 * Кой може да изтрива?
	 */
	public $canDelete = 'eshop,ceo';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Ешоп артикул,mandatory,input=hidden,silent');
		$this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=eshop_ProductDetails::getSellableProducts)', 'caption=Артикул,silent,removeAndRefreshForm=packagingId,mandatory');
		$this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack');
		$this->FLD('quantity', 'double', 'caption=Количество,input=none');
    	$this->FLD('quantityInPack', 'double', 'input=none');
    	$this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,input=none,smartCenter');
    	
		$this->setDbUnique('eshopProductId,productId,packagingId,quantity');
	}
	
	
	/**
	 * Изчисляване на количеството на реда в брой опаковки
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $rec
	 */
	protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
	{
		if (empty($rec->quantity) || empty($rec->quantityInPack)) return;
	
		$rec->packQuantity = $rec->quantity / $rec->quantityInPack;
	}
	
	
	/**
     * Връща достъпните продаваеми артикули
     *
     * @param array $params
     * @param NULL|integer $limit
     * @param string $q
     * @param NULL|integer|array $onlyIds
     * @param boolean $includeHiddens
     *
     * @return array
     */
    public static function getSellableProducts($params, $limit = NULL, $q = '', $onlyIds = NULL, $includeHiddens = FALSE)
    {
    	$products = array();
    	$pQuery = cat_Products::getQuery();
    	$pQuery->where("#state != 'closed' AND #state != 'rejected' AND #isPublic = 'yes' AND #canSell = 'yes'");
    	
    	if(is_array($onlyIds)) {
    		if(!count($onlyIds)) return array();
    		$ids = implode(',', $onlyIds);
    		expect(preg_match("/^[0-9\,]+$/", $onlyIds), $ids, $onlyIds);
    		$pQuery->where("#id IN ($ids)");
    	} elseif(ctype_digit("{$onlyIds}")) {
    		$pQuery->where("#id = $onlyIds");
    	}
    	
    	$xpr = "CONCAT(' ', #name, ' ', #code)";
    	$pQuery->XPR('searchFieldXpr', 'text', $xpr);
    	$pQuery->XPR('searchFieldXprLower', 'text', "LOWER({$xpr})");
    	
    	if($q) {
    		if($q{0} == '"') $strict = TRUE;
    		$q = trim(preg_replace("/[^a-z0-9\p{L}]+/ui", ' ', $q));
    		$q = mb_strtolower($q);
    		$qArr = ($strict) ? array(str_replace(' ', '.*', $q)) : explode(' ', $q);
    	
    		$pBegin = type_Key2::getRegexPatterForSQLBegin();
    		foreach($qArr as $w) {
    			$pQuery->where(array("#searchFieldXprLower REGEXP '(" . $pBegin . "){1}[#1#]'", $w));
    		}
    	}
    		
    	if($limit) {
    		$pQuery->limit($limit);
    	}
    	
    	$pQuery->show('id,name,code,isPublic,searchFieldXpr');
    	
    	while($pRec = $pQuery->fetch()) {
    		$products[$pRec->id] = cat_Products::getRecTitle($pRec, FALSE);
    	}
    	
    	return $products;
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	protected static function on_AfterPrepareEditForm($mvc, &$data)
	{
		$form = &$data->form;
		$rec = $form->rec;
		
		if(isset($rec->productId)){
			$form->setField('packagingId', 'input');
			$form->setField('packQuantity', 'input');
			$packs = cat_Products::getPacks($rec->productId);
			$form->setOptions('packagingId', $packs);
			$form->setDefault('packagingId', key($packs));
			$form->setDefault('packQuantity', 1);
		}
	}
	
	
	/**
	 * Извиква се след въвеждането на данните от Request във формата ($form->rec)
	 *
	 * @param core_Mvc $mvc
	 * @param core_Form $form
	 */
	protected static function on_AfterInputEditForm($mvc, &$form)
	{
		$rec = $form->rec;
		
		if($form->isSubmitted()){
			
			// Проверка на к-то
			if(!deals_Helper::checkQuantity($rec->packagingId, $rec->packQuantity, $warning)){
				$form->setError('packQuantity', $warning);
			}
			
			// Ако артикула няма опаковка к-то в опаковка е 1, ако има и вече не е свързана към него е това каквото е било досега, ако още я има опаковката обновяваме к-то в опаковка
			if(!$form->gotErrors()){
				$productInfo = cat_Products::getProductInfo($rec->productId);
				$rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
				$rec->quantity = $rec->packQuantity * $rec->quantityInPack;
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
	protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-list'])){
			$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
			$row->eshopProductId = eshop_Products::getHyperlink($rec->eshopProductId, TRUE);
			
			deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
		}
	}
	
	
	/**
	 * Преди подготовката на полетата за листовия изглед
	 */
	protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
	{
		if(isset($data->masterMvc)){
			unset($data->listFields['eshopProductId']);
		}
	}
}
<?php



/**
 * Клас 'store_Products' за наличните в склада артикули
 * Данните постоянно се опресняват от баланса
 *
 *
 * @category  bgerp
 * @package   store
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class store_Products extends core_Manager
{
    
    
	/**
	 * Ключ с който да се заключи ъпдейта на таблицата
	 */
	const SYNC_LOCK_KEY = 'syncStoreProducts';
    
    
    /**
     * Заглавие
     */
    public $title = 'Продукти';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, store_Wrapper, plg_StyleNumbers, plg_Sorting, plg_AlignDecimals2, plg_State';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId=Наименование, measureId=Мярка,quantity,reservedQuantity,freeQuantity,storeId';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Име,remember=info');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('quantity', 'double', 'caption=Налично');
        $this->FLD('reservedQuantity', 'double', 'caption=Запазено');
        $this->FNC('freeQuantity', 'double', 'caption=Разполагаемо');
        $this->FLD('state', 'enum(active=Активирано,closed=Изчерпано)', 'caption=Състояние,input=none');
        
        $this->setDbUnique('productId, storeId');
        $this->setDbIndex('productId');
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, $data)
    {
        // Ако няма никакви записи - нищо не правим
        if(!count($data->recs)) return;
	            
	    foreach($data->rows as $id => &$row){
	       $rec = &$data->recs[$id];
	       $row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
	       $row->storeId = store_Stores::getHyperlink($rec->storeId, TRUE);
	       if(isset($rec->reservedQuantity)){
	       		$rec->freeQuantity = $rec->quantity - $rec->reservedQuantity;
	       		$row->freeQuantity = $mvc->getFieldType('freeQuantity')->toVerbal($rec->freeQuantity);
	       }
	       
	       try{
	        	$measureId = cat_Products::fetchField($rec->productId, 'measureId');
	        	$row->measureId = cat_UoM::getTitleById($measureId);
	       } catch(core_exception_Expect $e){
	        	$row->measureId = tr("???");
	       }
        }
    }
    
    
    /**
     * След подготовка на филтъра
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
    	// Подготвяме формата
    	cat_Products::expandFilter($data->listFilter);
    	$orderOptions = arr::make('all=Всички,active=Активни,standard=Стандартни,private=Нестандартни,last=Последно добавени,closed=Изчерпани');
    	$data->listFilter->setOptions('order', $orderOptions);
		$data->listFilter->setDefault('order', 'active');
    	
    	$data->listFilter->FNC('search', 'varchar', 'placeholder=Търсене,caption=Търсене,input,silent,recently');
    	
    	$stores = array();
    	$sQuery = store_Stores::getQuery();
    	$sQuery->where("#state != 'rejected'");
    	store_Stores::restrictAccess($sQuery);
    	while($sRec = $sQuery->fetch()){
    		$stores[$sRec->id] = store_Stores::getTitleById($sRec->id, FALSE);
    	}
    	$data->listFilter->setOptions('storeId', array('' => '') + $stores);
    	$data->listFilter->setDefault('storeId', store_Stores::getCurrent('id', FALSE));
    	
    	$data->listFilter->setField('storeId', 'autoFilter');
    	
    	// Подготвяме в заявката да може да се търси по полета от друга таблица
    	$data->query->EXT('keywords', 'cat_Products', 'externalName=searchKeywords,externalKey=productId');
    	$data->query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
    	$data->query->EXT('groups', 'cat_Products', 'externalName=groups,externalKey=productId');
    	$data->query->EXT('name', 'cat_Products', 'externalName=name,externalKey=productId');
    	$data->query->EXT('productCreatedOn', 'cat_Products', 'externalName=createdOn,externalKey=productId');
    	
        $data->listFilter->showFields = 'storeId,search,order,groupId';
        $data->listFilter->input('storeId,order,groupId,search', 'silent');
        
        // Ако има филтър
        if($rec = $data->listFilter->rec){
        	
        	// И е избран склад, търсим склад
        	if(isset($rec->storeId)){
        		$selectedStoreName = store_Stores::getHyperlink($rec->storeId, TRUE);
        		$data->title = "|Наличности в склад|* <b style='color:green'>{$selectedStoreName}</b>";
        		$data->query->where("#storeId = {$rec->storeId}");
        	}
        	
        	// Ако се търси по ключови думи, търсим по тези от външното поле
        	if(isset($rec->search)){
        	 	plg_Search::applySearch($rec->search, $data->query, 'keywords');
            
            	// Ако ключовата дума е число, търсим и по ид
            	if (type_Int::isInt($rec->search)) {
            		$data->query->orWhere("#productId = {$rec->search}");
            	}
        	}
        	
        	// Подредба
        	if(isset($rec->order)){
        		switch($data->listFilter->rec->order){
        			case 'all':
        				$data->query->orderBy('#state,#name');
						break;
		        	case 'private':
        				$data->query->where("#isPublic = 'no'");
        				$data->query->orderBy('#state,#name');
						break;
					case 'last':
			      		$data->query->orderBy('#createdOn=DESC');
		        		break;
        			case 'closed':
        				$data->query->where("#state = 'closed'");
        				break;
        			case 'active':
        				$data->query->where("#state != 'closed'");
        				break;
        			default :
        				$data->query->where("#isPublic = 'yes'");
        				$data->query->orderBy('#state,#name');
        				break;
        		}
        	}
        	
        	// Филтър по групи на артикула
        	if (!empty($rec->groupId)) {
        		$descendants = cat_Groups::getDescendantArray($rec->groupId);
        		$keylist = keylist::fromArray($descendants);
        		$data->query->likeKeylist("groups", $keylist);
        	}
        }
    }
    
    
    /**
     * Синхронизиране на запис от счетоводството с модела, Вика се от крон-а
     * (@see acc_Balances::cron_Recalc)
     * 
     * @param array $all - масив идващ от баланса във вида:
     * 				array('store_id|class_id|product_Id' => 'quantity')
     */
    public static function sync($all)
    {
    	$query = static::getQuery();
    	$query->show('productId,storeId,quantity,state');
    	$oldRecs = $query->fetchAll();
    	$self = cls::get(get_called_class());
    	
    	$arrRes = arr::syncArrays($all, $oldRecs, "productId,storeId", "quantity");
    	
    	if(!core_Locks::get(self::SYNC_LOCK_KEY, 60, 1)) {
    		$this->logWarning("Синхронизирането на складовите наличности е заключено от друг процес");
    		return;
    	}
    	
    	$self->saveArray($arrRes['insert']);
    	$self->saveArray($arrRes['update']);
    	
    	// Ъпдейт на к-та на продуктите, имащи запис но липсващи в счетоводството
    	self::updateMissingProducts($arrRes['delete']);
    	
    	core_Locks::release(self::SYNC_LOCK_KEY);
    }
    
    
    /**
     * Ф-я която ъпдейтва всички записи, които присъстват в модела, 
     * но липсват в баланса
     * 
     * @param array $array - масив с данни за наличните артикул
     */
    private static function updateMissingProducts($array)
    {
    	// Всички записи, които са останали но не идват от баланса
    	$query = static::getQuery();
    	$query->show('productId,storeId,quantity,state');
    	
    	// Зануляваме к-та само на тези продукти, които още не са занулени
    	$query->where("#state = 'active'");
    	if(count($array)){
    		
    		// Маркираме като затворени, всички които не са дошли от баланса или имат количества 0
    		$query->in('id', $array);
    		$query->orWhere("#quantity = 0");
    	}
    	
    	if(!count($array)) return;
    	
    	// За всеки запис
    	while($rec = $query->fetch()){
    		
    		// К-то им се занулява и състоянието се затваря
    		$rec->state    = 'closed';
    		$rec->quantity = 0;
    		
    		// Обновяване на записа
    		static::save($rec);
    	}
    }
    
    
    /**
     * Колко е количеството на артикула в складовете
     * 
     * @param int $productId    - ид на артикул
     * @param int|NULL $storeId - конкретен склад, NULL ако е във всички
     * @return double $sum      - наличното количество
     */
    public static function getQuantity($productId, $storeId = NULL)
    {
    	$sum = 0;
    	$query = self::getQuery();
    	$query->where("#productId = {$productId}");
    	$query->show('quantity');
    	if(isset($storeId)){
    		$query->where("#storeId = {$storeId}");
    	}
    	
    	while($r = $query->fetch()){
    		$sum += $r->quantity;
    	}
    	
    	return $sum;
    }
    
    
    /**
     * Всички налични к-ва на артикулите в склад-а
     * 
     * @param int $storeId
     * @return array $res
     */
    public static function getQuantitiesInStore($storeId)
    {
    	$res = array();
    	$query = self::getQuery();
    	$query->where("#storeId = {$storeId}");
    	$query->show('productId,quantity');
    	while($rec = $query->fetch()){
    		$res[$rec->productId] = $rec->quantity;
    	}
    	
    	return $res;
    }
    
    
    /**
     * Връща всички продукти в склада
     * 
     * @param NULL|int $storeId - ид на склад, ако е NULL взима текущия активен склад
     * @return array $products
     */
    public static function getProductsInStore($storeId = NULL)
    {
    	// Ако няма склад, взима се текущия
    	if(!isset($storeId)){
    		$storeId = store_Stores::getCurrent();
    	}
    	
    	$products = array();
	    $pQuery = static::getQuery();
	    $pQuery->where("#storeId = {$storeId}");
	    
	    while($pRec = $pQuery->fetch()){
	        $products[$pRec->id] = cat_Products::getTitleById($pRec->productId, FALSE);
	    }
	    
	    return $products;
    }


    /**
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
    	if(haveRole('debug')){
    		$data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искате ли да изчистите таблицата, ef_icon=img/16/sport_shuttlecock.png, title=Изтриване на таблицата с продукти');
    	}
    }
    
    
    /**
     * Изчиства записите в склада
     */
    public function act_Truncate()
    {
    	requireRole('debug');
    	 
    	// Изчистваме записите от моделите
    	store_Products::truncate();
    	 
    	return new Redirect(array($this, 'list'));
    }
    
    
    /**
     * Проверяваме дали колонката с инструментите не е празна, и ако е така я махаме
     */
    public static function on_BeforeRenderListTable($mvc, &$res, $data)
    {
    	$data->listTableMvc->FLD('measureId', 'varchar', 'smartCenter');
    }


	/**
	 * Преди подготовката на ключовете за избор
	 */
    public static function on_BeforePrepareKeyOptions($mvc, &$options, $typeKey, $where = '')
    {
        $storeId = store_Stores::getCurrent();
        $query = self::getQuery();
        if($where) {
            $query->where($where);
        }
        while($rec = $query->fetch("#storeId = {$storeId}  AND #state = 'active'")) {
            $options[$rec->id] = self::getVerbal($rec, 'productId');
        }

        if(!count($options)) {
            $options[''] = '';
        }
    }

    
    /**
     * Обновяване на резервираните наличности по крон
     */
    function cron_CalcReservedQuantity()
    {
    	// Ще се преизчисляват ли резервациите
    	if(!$this->doRecalcReservedQuantities()) return;
    	$containerIds = $result = $reserveDetails = array();
    	
    	$saleQuery = sales_Sales::getQuery();
    	$saleQuery->where("#state = 'active'");
    	$saleQuery->show('containerId');
    	$containerIds = arr::extractValuesFromArray($saleQuery->fetchAll(), 'containerId');
    	
    	$jobQuery = planning_Jobs::getQuery();
    	$jobQuery->where("#state = 'active' || #state = 'stopped' || #state = 'wakeup'");
    	$jobQuery->show('containerId');
    	$containerIds1 = arr::extractValuesFromArray($jobQuery->fetchAll(), 'containerId');
    	$containerIds += $containerIds1;
    	
    	// Сумират се резервираните количества
    	$query = store_ReserveStockDetails::getQuery();
    	$query->EXT('state', 'store_ReserveStocks', 'externalName=state,externalKey=reserveId');
    	$query->EXT('originId', 'store_ReserveStocks', 'externalName=originId,externalKey=reserveId');
    	$query->where("#state = 'active'");
    	$query->show('reserveId,quantity,productId,originId');
    	$query->in('originId', $containerIds);
    	
    	// Групират се резервираните количества по артикули и склад
    	while($dRec = $query->fetch()){
    		
    		if(!is_array($reserveDetails[$dRec->reserveId])){
    			$reserveDetails[$dRec->reserveId] = array();
    		}
    		
    		if(!array_key_exists($dRec->productId, $reserveDetails[$dRec->reserveId])){
    			$reserveDetails[$dRec->reserveId][$dRec->productId] = $dRec->quantity;
    		} else {
    			$reserveDetails[$dRec->reserveId][$dRec->productId] += $dRec->quantity;
    		}
    	}
    	
    	// Намират се всички активини РнСН
    	$query = store_ReserveStocks::getQuery();
    	$query->where("#state = 'active'");
    	$query->show('storeId,threadId,activatedOn');
    	while($rec = $query->fetch()){
    		
    		// Ако е празен се пропуска
    		$details = $reserveDetails[$rec->id];
    		if(!count($details)) continue;
    		
    		// Намират се всички експедирани артикули с ЕН, активирани след резервацията
    		$shQuery = store_ShipmentOrderDetails::getQuery();
    		$shQuery->EXT('state', 'store_ShipmentOrders', 'externalName=state,externalKey=shipmentId');
    		$shQuery->EXT('threadId', 'store_ShipmentOrders', 'externalName=threadId,externalKey=shipmentId');
    		$shQuery->EXT('activatedOn', 'store_ShipmentOrders', 'externalName=activatedOn,externalKey=shipmentId');
    		$shQuery->where("#state = 'active'");
    		$shQuery->where("#activatedOn >= '{$rec->activatedOn}'");
    		$shQuery->where("#threadId = '{$rec->threadId}'");
    		$shQuery->show('productId,quantity,shipmentId');
    		
    		// Ако има резервирано количество за този артикул, приспада се
    		while($shRec = $shQuery->fetch()){
    			if(isset($details[$shRec->productId])){
    				$details[$shRec->productId] -= $shRec->quantity;
    			}
    		}
    			
    		// Намират се всички експедирани артикули с протокол за производство в нишката.
    		// Активирани след активирането на резервацията
    		$pQuery = planning_DirectProductNoteDetails::getQuery();
    		$pQuery->EXT('state', 'planning_DirectProductionNote', 'externalName=state,externalKey=noteId');
    		$pQuery->EXT('threadId', 'planning_DirectProductionNote', 'externalName=threadId,externalKey=noteId');
    		$pQuery->EXT('activatedOn', 'planning_DirectProductionNote', 'externalName=activatedOn,externalKey=noteId');
    		$pQuery->where("#type = 'input'");
    		$pQuery->where("#state = 'active'");
    		$pQuery->where("#activatedOn >= '{$rec->activatedOn}'");
    		$pQuery->where("#threadId = '{$rec->threadId}'");
    		$pQuery->where("#storeId IS NOT NULL");
    			
    		// Ако има резервирано количество приспада се
    		while($pRec = $pQuery->fetch()){
    			if(isset($details[$pRec->productId])){
    				$details[$pRec->productId] -= $pRec->quantity;
    			}
    		}
    			
    		// За останалите записи, подготвя се записите за ъпдейт
    		foreach ($details as $productId => $quantity){
    			$key = "{$rec->storeId}|{$productId}";
    			if(!array_key_exists($key, $result)){
    				$result[$key] = (object)array('storeId' => $rec->storeId, 'productId' => $productId, 'reservedQuantity' => $quantity, 'state' => 'active');
    			} else {
    				$result[$key]->reservedQuantity += $quantity;
    			}
    		}
    	}
    	
    	// Извличане на всички стари записи
    	$storeQuery = static::getQuery();
    	$old = $storeQuery->fetchAll();
    	
    	// Синхронизират се новите със старите записи
    	$res = arr::syncArrays($result, $old, 'storeId,productId', 'reservedQuantity');
    	
    	// Заклюване на процеса
    	if(!core_Locks::get(self::SYNC_LOCK_KEY, 60, 1)) {
    		$this->logWarning("Синхронизирането на складовите наличности е заключено от друг процес");
    		return;
    	}
    	
    	// Добавяне и ъпдейт на резервираното количество на новите
    	$this->saveArray($res['insert']);
    	$this->saveArray($res['update'], 'id,reservedQuantity');
    		
    	// Намиране на тези записи, от старите които са имали резервирано к-во, но вече нямат
    	$unsetArr = array_filter($old, function (&$r) use ($result) {
    		if(!isset($r->reservedQuantity)) return FALSE;
    		if(array_key_exists("{$r->storeId}|{$r->productId}", $result)){
    			return FALSE;
    		}
    			
    		return TRUE;
    	});
    		
    	// Техните резервирани количества се изтриват
    	if(count($unsetArr)){
    		array_walk($unsetArr, function($obj){$obj->reservedQuantity = NULL;});
    		$this->saveArray($unsetArr, 'id,reservedQuantity');
    	}
    	
    	// Освобождаване на процеса
    	core_Locks::release(self::SYNC_LOCK_KEY);
    }
    
    
    /**
     * Дали трябва да се преизчисляват запазените количества.
     * Те ще се преизчисляват ако поне едно е изпълнено от изброените.
     * 
     * 1. Има нови активни/оттеглени РнСН активирани/оттеглени след $timeline 
     * 2. Има ли нови контирани или анулирани ЕН-та в нишките на активните РнСН
     * 3. Има ли нови контирани или анулирани Протоколи за производство в нишките на активните РнСН
     * 4. Ако поне от горните не е изпълнено нещо, няма да се преизчисляват
     * 
     * @return boolean - TRUE или FALSE
     */
    private function doRecalcReservedQuantities()
    {
    	//$timeline = dt::addSecs(-10 * 60, dt::now());
    	
    	// Има ли активирани РнСН след $timeline, или има оттеглени РнСН след $timeline
    	$rQuery1 = store_ReserveStocks::getQuery();
    	$rQuery1->where("#activatedOn >= '{$timeline}' AND #state = 'active'");
    	$rQuery1->orWhere("#modifiedOn >= '{$timeline}' AND #state = 'rejected'");
    	$rQuery1->show('id');
    	if($rQuery1->count()) return TRUE;
    	
    	// Извличане на всички нишки на активни РнСН
    	$threadIds = store_ReserveStocks::getThreads();
    	if(!count($threadIds)) return FALSE;
    	
    	// Проверяват се всички ЕН, СР и протоколи за производство в нишките
    	foreach (array('store_ShipmentOrders', 'planning_DirectProductionNote') as $doc){
    		$mvc = cls::get($doc);
    			
    		// Има ли активирани документи след $timeline, или има оттеглени документи след $timeline
    		$query = $mvc->getQuery();
    		$query->in('threadId', $threadIds);
    		$query->where("#activatedOn >= '{$timeline}' AND #state = 'active'");
    		$query->orWhere("#modifiedOn >= '{$timeline}' AND #state = 'rejected' AND #brState = 'active'");
    			
    		if($query->count()) return TRUE;
    	}
    	
    	// Ако се стигне до тук, няма промяна
    	return FALSE;
    }
}
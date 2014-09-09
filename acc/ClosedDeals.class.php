<?php
/**
 * Клас 'acc_ClosedDeals'
 * Абстрактен клас за създаване на приключващи документи. Неговите наследници
 * могат да се създават само в тред, началото на който е документ с интерфейс
 *'bgerp_DealAggregatorIntf'. След контирането на този документ, не може в треда
 * да се добавят документи, променящи стойностите на сделката
 * 
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
abstract class acc_ClosedDeals extends core_Master
{
    
    
    /**
     * Кой има право да чете?
     */
    protected $canRead = 'no_one';
    
    
    /**
     * Кой има право да променя?
     */
    protected $canWrite = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    protected $canAdd = 'no_one';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	protected $canList = 'no_one';
    
    
	/**
     * Икона за фактура
     */
    public $singleIcon = 'img/16/closeDeal.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    protected $listFields = 'id, docId=Документ, createdBy, createdOn';
	
	
	/**
     * Файл за единичен изглед
     */
    protected $singleLayoutFile = 'acc/tpl/ClosedDealsSingleLayout.shtml';
    
    
    /**
     * Работен кеш
     */
    protected static $cache = array();
    
    
    /**
     * Още един работен кеш
     */
    protected static $incomeAmount;
    
    
    /**
     * Още един работен кеш
     */
    protected $year;
    
    
    /**
     * Кратък баланс на записите от журнала засегнали сделката
     */
    protected $shortBalance;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
    	$this->FLD('notes', 'richtext(rows=2)', 'caption=Забележка,mandatory');
    	
    	// Класа на документа, който се затваря
    	$this->FLD('docClassId', 'class(interface=doc_DocumentIntf)', 'input=none');
    	
    	// Ид-то на документа, който се затваря
    	$this->FLD('docId', 'class(interface=doc_DocumentIntf)', 'input=none');
    	$this->FLD('amount', 'double(decimals=2)', 'input=none,caption=Сума');
    	$this->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code)','caption=Плащане->Валута,input=none');
        $this->FLD('rate', 'double', 'caption=Плащане->Курс,input=none');
        
    	// От кой клас наследник на acc_ClosedDeals идва записа
    	$this->FLD('classId', 'key(mvc=core_Classes)', 'input=none');
    }


    /**
     * Подготвя записите за приключване на дадена сделка с друга сделка
     *
     * 1. Занулява салдата на първата сделка, прави обратни транзакции на всички записи от журнала свързани с тази сделка
     * 2. Прави същите операции но подменя перото на първата сделка с това на второто, така всички салда са
     * прихвърлени по втората сделка, а първата е приключена
     */
    public function getTransferEntries($dealItem, &$total, $closeDealItem, $rec)
    {
    	$newEntries = array();
    	$docs = array();
    	 
    	// Намираме записите в които участва перото
    	$entries = acc_Journal::getEntries($dealItem, $item);
    	
    	// Намираме документите, които имат транзакции към перото
    	if(count($entries)){
    		foreach ($entries as $ent){
	    		if($ent->docType != $rec->classId && $ent->docId != $rec->id){
	 				$docs[$ent->docType . "|" . $ent->docId] = (object)array('docType' => $ent->docType, 'docId' => $ent->docId);
				}
    		}
    	}
    	
    	$dealItem->docClassName = cls::get($dealItem->classId)->className;
    	
    	if(count($docs)){
    
    		// За всеки транзакционен клас
    		foreach ($docs as $doc){
    			 
    			// Взимаме му редовете на транзакцията
    			$transactionSource = cls::getInterface('acc_TransactionSourceIntf', $doc->docType);
    			$entries = $transactionSource->getTransaction($doc->docId)->entries;
    			 
    			$copyEntries = $entries;
    			 
    			// За всеки ред, генерираме запис с обратни стойностти (сумите и к-та са с обратен знак)
    			// Така зануляване салдата по следката
    			if(count($entries)){
    				foreach ($copyEntries as &$entry){
    						
    					// Ако има сума добавяме я към общата сума на транзакцията
    					if(isset($entry['amount'])){
    						$entry['amount'] *= -1;
    						$total += $entry['amount'];
    					}
    					if(isset($entry['debit']['quantity'])){
    						$entry['debit']['quantity'] *= -1;
    					}
    					if(isset($entry['credit']['quantity'])){
    						$entry['credit']['quantity'] *= -1;
    					}
    						
    					$newEntries[] = $entry;
    				}
    
    				// Втори път обхождаме записите
    				foreach ($entries as &$entry2){
    					if(isset($entry2['amount'])){
    						$total += $entry2['amount'];
    					}
    						
    					// Генерираме запис, който прави същите действия но с перо новата сделка
    					foreach (array('debit', 'credit') as $type){
    						foreach ($entry2[$type] as $index => &$item){
    								
    							// Намираме кое перо отговаря на перото на текущата сделка и го заменяме с това на новата сделка
    							if($index != 0){
    								if(is_array($item) && $item[0] == $dealItem->docClassName && $item[1] == $dealItem->objectId){
    									$item = $closeDealItem->id;
    								}
    							}
    						}
    					}
    						
    					$newEntries[] = $entry2;
    				}
    			}
    		}
    	}
    	 
    	// Връщаме генерираните записи
    	return $newEntries;
    }
    
    
    /**
     * Връща информацията 'bgerp_DealAggregatorIntf' от първия документ
     * в нишката ако го поддържа
     * @param mixed  $threadId - ид на нишката или core_ObjectReference
     * 							 към първия документ в нишката
     * @return bgerp_iface_DealAggregator - бизнес информацията от документа
     */
    public static function getDealInfo($threadId)
    {
    	$firstDoc = (is_numeric($threadId)) ? doc_Threads::getFirstDocument($threadId) : $threadId;
    	
    	expect($firstDoc instanceof core_ObjectReference, $firstDoc);
    	$threadId = $firstDoc->fetchField('threadId');
    	if($firstDoc->haveInterface('bgerp_DealAggregatorIntf')){
	    	if(empty(static::$cache[$threadId])){
	    		
	    		// Запис във временния кеш
	    		expect($dealInfo = $firstDoc->getAggregateDealInfo());
	    		static::$cache[$threadId] = $dealInfo;
	    	}
	    	
	    	return static::$cache[$threadId];
    	}
    	
    	return FALSE;
    }
    
    
	/**
     * Преди показване на форма за добавяне/промяна
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	$rec = &$form->rec;
    	$firstDoc = doc_Threads::getFirstDocument($rec->threadId);
    	$rec->docId = $firstDoc->that;
    	$rec->docClassId = $firstDoc->instance()->getClassId();
    	$rec->classId = $mvc->getClassId();
    }
    
    
	/**
     * Може ли документ-продажба да се добави в посочената папка?
     */
    public static function canAddToFolder($folderId)
    {
        return FALSE;
    }
    
    
	/**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     */
	public static function canAddToThread($threadId)
    {
    	// Първия документ в треда трябва да е активиран
    	$firstDoc = doc_Threads::getFirstDocument($threadId);
    	
    	if(!$firstDoc) return FALSE;
    	
    	// Може да се добавя само към ниша с първи документ имащ 'bgerp_DealAggregatorIntf'
    	if(!$firstDoc->haveInterface('bgerp_DealAggregatorIntf')) return FALSE;
    	
    	// Може да се добавя само към активирани документи
    	if($firstDoc->fetchField('state') != 'active') return FALSE;
		
    	// Дали вече има такъв документ в нишката
    	$closedDoc = static::fetch("#threadId = {$threadId} AND #state != 'rejected'");
    	if($closedDoc !== FALSE) return FALSE;
    	
    	return TRUE;
    }
	
	
    /**
	 * След подготовка на лист тулбара
	 */
	public static function on_AfterPrepareListToolbar($mvc, $data) 
	{
		if (!empty ($data->toolbar->buttons ['btnAdd'])) {
			unset($data->toolbar->buttons['btnAdd']);
		}
	}
    
    
	/**
	 * Изпълнява се след запис
	 */
	public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
    	// При активация на документа
    	$rec = $mvc->fetch($id);
    	if($rec->state == 'active'){
    		
    		// Пораждащия документ става closed
    		$DocClass = cls::get($rec->docClassId);
	    	$firstRec = $DocClass->fetch($rec->docId);
	    	$firstRec->state = 'closed';
	    	$DocClass->save($firstRec);
	    	
	    	// Ако има перо сделката, затваряме го
	    	if($item = acc_Items::fetchItem($DocClass->getClassId(), $firstRec->id)){
	    		
	    		// Изчистваме заопашените пера, ако ги има за да им се обнови 'lastUsedOn'
	    		$Items = cls::get('acc_Items');
	    		$Items->flushTouched();
	    		
	    		acc_Lists::removeItem($DocClass, $firstRec->id, $item->lists);
	    		
	    		if(haveRole('ceo,acc,debug')){
	    			$title = $DocClass->getTitleById($firstRec->id);
	    			core_Statuses::newStatus(tr("|Перото|* \"{$title}\" |е затворено/изтрито|*"));
	    		}
	    	}
	    	
	    	if(empty($saveFileds)){
	    		$rec->amount = $mvc::getClosedDealAmount($rec->threadId);
	    		$mvc->save($rec, 'amount');
	    	}
    	}
    }
    
    
    /**
     * След оттегляне на документа, възстановява предишното
     * състояние на първия документ в нишката
     */
    public static function on_AfterReject($mvc, &$res, $id)
    {
    	$rec = $mvc->fetch((is_object($id)) ? $id->id : $id);
    	
    	if($rec->brState == 'active'){
    		$DocClass = cls::get($rec->docClassId);
		    $firstRec = $DocClass->fetch($rec->docId);
		    
		    // Обновяваме състоянието на сделката, само ако не е оттеглена
		    if($firstRec->state != 'rejected'){
		    	$firstRec->state = 'active';
		    	$DocClass->save($firstRec);
		    }
		    
		    // Ако има перо сделката, обновяваме му състоянието
		    if($item = acc_Items::fetchItem($DocClass->getClassId(), $firstRec->id)){
		    	acc_Lists::updateItem($DocClass, $firstRec->id, $item->lists);
		    	
		    	if(haveRole('ceo,acc,debug')){
		    		$msg = tr("Активирано е перо|* '") . $DocClass->getTitleById($firstRec->id) . tr("' |в номенклатура 'Сделки'|*");
		    		core_Statuses::newStatus($msg);
		    	}
		    }
    	}
    	
    	$mvc->notificateDealUsedForClosure($id);
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    static function recToVerbal_($rec, &$fields = '*')
    {
    	$row = parent::recToVerbal_($rec, $fields);
    	
    	$Double = cls::get('type_Double');
    	$Double->params['decimals'] = 2;
    	
    	$costAmount = $incomeAmount = 0;
    	if($rec->state == 'active'){
    		if(round($rec->amount, 2) > 0){
    			$incomeAmount = $rec->amount;
    			$costAmount = 0;
    		} elseif(round($rec->amount, 2) < 0){
    			$costAmount = $rec->amount;
    			$incomeAmount = 0;
    		}
    		
    		$Double = cls::get('type_Double');
    		$Double->params['decimals'] = 2;
    	}
    	
    	$row->costAmount = $Double->toVerbal(abs($costAmount));
    	$row->incomeAmount = $Double->toVerbal(abs($incomeAmount));
    	
    	$row->currencyId = acc_Periods::getBaseCurrencyCode($rec->createdOn);
	    
	    $abbr = cls::get(get_called_class())->abbr;
	    $row->header = cls::get(get_called_class())->singleTitle . " #<b>{$abbr}{$row->id}</b> ({$row->state})";
	    
	    $row->docId = cls::get($rec->docClassId)->getHyperLink($rec->docId, TRUE);
	    
	    return $row;
    }
    
    
	/**
     * Имплементиране на интерфейсен метод (@see doc_DocumentIntf)
     */
    function getDocumentRow($id)
    {
    	$rec = $this->fetch($id);
    	$row = new stdClass();
    	
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->state = $rec->state;
		$row->saleId = cls::get($rec->docClassId)->getHandle($rec->docId);
        
        return $row;
    }
    
    
 	/**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	// Документа не може да се контира, ако ориджина му е в състояние 'closed'
    	if($action == 'conto' && isset($rec)){
    		$origin = $mvc->getOrigin($rec);
    		if($origin && $origin->haveInterface('bgerp_DealAggregatorIntf')){
	    		$originState = $origin->fetchField('state');
	    		if($originState === 'closed'){
		        	$res = 'no_one';
		        }
	    	}
        }
        
        // не може да се възстанови оттеглен документ, ако има друг неоттеглен в треда
        if($action == 'restore' && isset($rec)){
        	if($mvc->fetch("#threadId = {$rec->threadId} AND #state != 'rejected' AND #id != '{$rec->id}'")){
        		$res = 'no_one';
        	}
        }
    }
    
    
	/**
     * Преди извличане на записите от БД
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$plugins = $mvc->getPlugins();
    	$docClassId = NULL;
    	
    	if(isset($plugins['sales_Wrapper'])){
    		$docClassId = sales_Sales::getClassId();
    	} elseif(isset($plugins['purchase_Wrapper'])){
    		$docClassId = purchase_Purchases::getClassId();
    	} elseif(isset($plugins['deals_Wrapper'])){
    		$docClassId = deals_Deals::getClassId();
    	}
    	
    	if($docClassId){
    		$data->query->where("#docClassId = {$docClassId}");
    		if(!$data->rejQuery){
    			$data->rejQuery = clone $data->query;
    			$data->rejQuery->where("#state = 'rejected'");
    		}
    		
    		$data->rejQuery->where("#docClassId = {$docClassId}");
    	}
    }
    
    
    /**
     * Нов приключващ документ в същия тред на даден документ, и
     * приключващ продажбата/покупката
     * 
     * @param mixed $Class - покупка или продажба
     * @param stdClass $docRec - запис на покупка или продажба
     * @param int $closeWith - с коя сделка ще се приключи продажбата
     */
    public function create($Class, $docRec, $closeWith = FALSE)
    {
    	$Class = cls::get($Class);
    	
    	// Създаване на приключващ документ, само ако има остатък/излишък
    	$newRec = new stdClass();
    	
	    $newRec->notes      = "Автоматично приключване";
	    $newRec->docClassId = $Class->getClassId();
	    $newRec->docId      = $docRec->id;
	    $newRec->folderId   = $docRec->folderId;
	    $newRec->threadId   = $docRec->threadId;
	    $newRec->state      = 'draft';
	    $newRec->classId    = $this->getClassId();
	    if($closeWith){
	    	$newRec->closeWith = $closeWith;
	    }
	    
	    // Създаване на документа
	    return static::save($newRec);
    }
    
    
	/**
     * Връща счетоводното основание за документа
     */
    public function getContoReason($id)
    {
    	$rec = $this->fetchRec($id);
    	
    	return $this->getVerbal($rec, 'notes');
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
    	$self = cls::get(get_called_class());
    	
    	return $self->singleTitle . " №{$rec->id}";
    }
    
    
    /**
     * Дали документа има приключени пера в транзакцията му
     */
    public function getClosedItemsInTransaction_($id)
    {
    	$rec = $this->fetchRec($id);
    	$closedItems = NULL;
    	
    	// Намираме приключените пера от транзакцията
    	$transaction = $this->getValidatedTransaction($id);
    	if($transaction){
    		$closedItems = $transaction->getClosedItems();
    	}
    	
    	// От списъка с приключените пера, премахваме това на приключения документ, така че да може
    	// приключването да се оттегля/възстановява въпреки че има в нея приключено перо
    	$dealItemId = acc_Items::fetchItem($rec->docClassId, $rec->docId)->id;
    	unset($closedItems[$dealItemId]);
    	
    	return $closedItems;
    }
    
    
    /**
     * Връща всички документи, които са приключили сделки с подадената сделка
     */
    public static function getClosedWithDeal($dealId)
    {
    	$closedDealQuery = self::getQuery();
    	$closeClassId = self::getClassId();
    	$closedDealQuery->where("#closeWith = {$dealId}");
    	$closedDealQuery->where("#classId = {$closeClassId}");
    	$closedDealQuery->where("#state = 'active'");
    	
    	return $closedDealQuery->fetchAll();
    }


    /**
     * След успешно контиране на документа
     */
    public static function on_AfterRestore($mvc, &$res, $id)
    {
    	$mvc->notificateDealUsedForClosure($id);
    }
    
    
    /**
     * След успешно контиране на документа
     */
    public static function on_AfterConto($mvc, &$res, $id)
    {
    	$mvc->notificateDealUsedForClosure($id);
    }
    
    
    /**
     * Нотифицира продажбата която е използвана да се приключи продажбата на документа
     */
    private function notificateDealUsedForClosure($id)
    {
    	$rec = $this->fetchRec($id);
    
    	// Ако ще се приключва с друга продажба
    	if(!empty($rec->closeWith) && $rec->state != 'draft'){
    		 
    		// Прехвърляме ги към детайлите на продажбата с която сме я приключили
    		$Doc = cls::get($rec->docClassId);
    		$Doc->invoke('AfterClosureWithDeal', array($rec->closeWith));
    	}
    }
}
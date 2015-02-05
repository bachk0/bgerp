<?php



/**
 * Мениджър на ресурсите на предприятието
 *
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Ресурси на предприятието
 */
class mp_Resources extends core_Master
{
    
    
	/**
	 * Интерфейси, поддържани от този мениджър
	 */
	public $interfaces = 'mp_ResourceAccRegIntf,acc_RegisterIntf';
	
	
    /**
     * Заглавие
     */
    public $title = 'Ресурси на предприятието';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, plg_Rejected, mp_Wrapper, acc_plg_Registry';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo,mp';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,mp';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,mp';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'admin,mp';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo,mp';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'tools=Пулт,title,type,createdOn,createdBy,lastUsedOn,state';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'tools';
    
    
    /**
     * Поле за еденичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Ресурс';
    
    
    /**
     * Шаблон за еденичен изглед
     */
    public $singleLayoutFile = 'mp/tpl/SingleLayoutResource.shtml';
    		
    		
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
    	$this->FLD('title', 'varchar', 'caption=Наименование,mandatory');
    	$this->FLD('type', 'enum(equipment=Оборудване,labor=Труд,material=Материал)', 'caption=Вид,mandatory,silent');
    	$this->FLD('measureId', 'key(mvc=cat_UoM,select=name,allowEmpty)', 'caption=Мярка,mandatory');
    	$this->FLD('selfValue', 'double', 'caption=Себестойност');
    	$this->FLD('systemId', 'varchar', 'caption=Системен №,input=none');
    	$this->FLD('lastUsedOn', 'datetime(format=smartTime)', 'caption=Последна употреба,input=none,column=none');
    	$this->FLD('bomId', 'key(mvc=techno2_Boms)', 'input=none');
    	$this->FLD('state', 'enum(active=Активиран,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull,default=active');
    	
    	// Поставяме уникален индекс
    	$this->setDbUnique('title');
    	$this->setDbUnique('systemId');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    function loadSetupData()
    {
    	$file = "mp/csv/Resources.csv";
    	$fields = array(0 => "title", 1 => 'type', '2' => 'systemId', '3' => 'measureId');
    	
    	$cntObj = csv_Lib::importOnce($this, $file, $fields);
    	
    	$query = $this->getQuery();
    	$query->where("#systemId IS NOT NULL");
    	
    	// Добавяме автоматично дефолтните ресурси като пера от номенклатура 'ресурси'
    	while($rec = $query->fetch()){
    		if(!acc_Items::fetchItem($this, $rec->id)){
    			$rec->lists = keylist::addKey($rec->lists, acc_Lists::fetchField(array("#systemId = '[#1#]'", 'resources'), 'id'));
    			acc_Lists::updateItem($this, $rec->id, $rec->lists);
    		}
    	}
    	
    	return $cntObj->html;
    }
    
    
    /**
     * Изпълнява се преди импортирването на данните
     */
    public static function on_BeforeImportRec($mvc, &$rec)
    {
    	$rec->createdBy = '-1';
    	$rec->measureId = cat_UoM::fetchBySinonim($rec->measureId)->id;
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    public static function on_AfterPrepareListFilter($mvc, &$data)
    {
    	$data->listFilter->FNC('rType', 'enum(all=Всички,equipment=Оборудване,labor=Труд,material=Материал)', 'caption=Тип,placeholder=aa');
    	$data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    	$data->listFilter->setDefault('rType', 'all');
    	$data->listFilter->showFields = 'rType';
    	$data->listFilter->view = 'horizontal';
    	
    	$data->listFilter->input();
    	
    	if($type = $data->listFilter->rec->rType){
    		if($type != 'all'){
    			$data->query->where("#type = '{$type}'");
    		}
    	}
    	
    	//$data->query->where("#bomId IS NULL");
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    public static function getItemRec($objectId)
    {
    	$self = cls::get(__CLASS__);
    	$result = NULL;
    
    	if ($rec = $self->fetch($objectId)) {
    		$result = (object)array(
    				'num' => $rec->id . "-r",
    				'title' => $rec->title,
    		);
    	}
    
    	return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    public static function itemInUse($objectId)
    {
    	// @todo!
    }
    
    
    /**
     * Изпълнява се след подготовката на титлата в единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
    	$dQuery = mp_ObjectResources::getQuery();
    	$dQuery->where("#resourceId = {$data->rec->id}");
    	
    	$data->detailRows = $data->detailRecs = array();
    	while($dRec = $dQuery->fetch()){
    		$data->detailRecs[$dRec->id] = $dRec;
    		$data->detailRows[$dRec->id] = mp_ObjectResources::recToVerbal($dRec);
    	}
    }
    
    
    /**
     * След рендиране на еденичния изглед
     */
    public static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
    	$table = cls::get('core_TableView');
    	$detailTpl = $table->get($data->detailRows, 'tools=Пулт,objectId=Обект');
    	$tpl->append($detailTpl, 'DETAILS');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$data)
    {
    	$form = &$data->form;
    	if($form->rec->createdBy == '-1'){
    		foreach(array('title', 'type', 'measureId') as $fld){
    			$form->setReadOnly($fld);
    		}
    	}
    	
    	$cCode = acc_Periods::getBaseCurrencyCode();
    	$form->setField('selfValue', "unit={$cCode}");
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
    	if($form->isSubmitted()){
    		if(!empty($form->rec->selfValue)){
    			$form->rec->selfValue = currency_CurrencyRates::convertAmount($form->rec->selfValue);
    		}
    	}
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string $res
     * @param string $action
     * @param stdClass $rec
     * @param int $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$res, $action, $rec = NULL, $userId = NULL)
    {
    	if(($action == 'delete' || $action == 'reject') && isset($rec)){
    		if(mp_ObjectResources::fetchField("#resourceId = '{$rec->id}'")){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'delete') && isset($rec)){
    		if(isset($rec->lastUsedOn)){
    			$res = 'no_one';
    		}
    	}
    	
    	if(($action == 'edit') && isset($rec)){
    		$res = $mvc->getRequiredRoles('edit');
    	}
    }
    
    
    /**
     * Връща себестойността на ресурса
     * 
     * @param int $id - ид на ресурса
     * @return double - себестойността му
     */
    public static function getSelfValue($id)
    {
    	expect($rec = static::fetch($id));
    	
    	return $rec->selfValue;
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass $rec
     */
    public static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
    	if(empty($rec->measureId)){
    		if($rec->type != 'labor'){
    			$rec->measureId = cat_UoM::fetchBySinonim('pcs')->id;
    		} else {
    			$rec->measureId = cat_UoM::fetchBySinonim('h')->id;
    		}
    	}
    }
}
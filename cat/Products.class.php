<?php



/**
 * Регистър на продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.11
 */
class cat_Products extends core_Master {
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = 'acc_RegisterIntf,cat_ProductAccRegIntf,techno_SpecificationFolderCoverIntf';
    
    
    /**
     * Заглавие
     */
    var $title = "Артикули в каталога";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew, plg_PrevAndNext, acc_plg_Registry, plg_Rejected, plg_State,
                     cat_Wrapper, plg_Sorting, bgerp_plg_Groups, plg_Printing, Groups=cat_Groups, doc_FolderPlg, plg_Select, plg_Search, bgerp_plg_Import';
    
    
    /**
     * Име на полето за групите на продуктите.
     * Използва се за целите на bgerp_plg_Groups
     */
    var $groupField = 'groups';


    /**
     * Име на полето с групите, в които се намира продукт. Използва се от groups_Extendable
     * 
     * @var string
     */
    var $groupsField = 'groups';

    
    /**
     * Детайла, на модела
     */
    var $details = 'Packagings=cat_products_Packagings,Params=cat_products_Params,Files=cat_products_Files,ObjectLists=acc_Items,PriceGroup=price_GroupOfProducts,PriceList=price_ListRules';
    
    
    /**
     * Наименование на единичния обект
     */
    var $singleTitle = "Артикул";
    
    
    /**
     * Икона за единичния изглед
     */
    var $singleIcon = 'img/16/wooden-box.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'name,code,groups,tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'name';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'powerUser';
    
    
    /**
     * Кой може да променя?
     */
    var $canEdit = 'cat,ceo';
    
    
    /**
     * Кой може да добавя?
     */
    var $canAdd = 'cat,ceo';
    
    
    /**
     * Кой може да го види?
     */
    var $canView = 'powerUser';
    
    
    /**
     * Кой може да го разгледа?
     */
    var $canList = 'powerUser';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'cat,ceo';
    
    
    /**
     * Кой може да го отхвърли?
     */
    var $canReject = 'cat,ceo';
    
    
    /**
     * Кой може да качва файлове
     */
    var $canWrite = 'ceo,cat';
    
    
    /**
     * Клас за елемента на обграждащия <div>
     */
    var $cssClass = 'folder-cover';
    
    
    /**  
     * Кой има право да променя системните данни?  
     */  
    var $canEditsysdata = 'ceo, cat';
    
    
    /**
     * Нов темплейт за показване
     */
    var $singleLayoutFile = 'cat/tpl/products/SingleProduct.shtml';
    
    
    /**
     * Кой има достъп до единичния изглед
     */
    var $canSingle = 'powerUser';
    
	
    /** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'name, code';
	
	
	/**
	 * Шаблон (ET) за заглавие на продукт
	 * 
	 * @var string
	 */
	public $recTitleTpl = '[#name#] ([#code#])';
	
	
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name', 'varchar', 'caption=Наименование, mandatory,remember=info,width=100%');
		$this->FLD('code', 'varchar(64)', 'caption=Код, mandatory,remember=info,width=15em');
        $this->FLD('eanCode', 'gs1_TypeEan', 'input,caption=EAN,width=15em');
		$this->FLD('info', 'richtext(bucket=Notes)', 'caption=Детайли');
        $this->FLD('measureId', 'key(mvc=cat_UoM, select=name)', 'caption=Мярка,mandatory,notSorting');
        $this->FLD('groups', 'keylist(mvc=cat_Groups, select=name, translate)', 'caption=Групи,maxColumns=2');
       	$this->FLD('photo', 'fileman_FileType(bucket=pictures)', 'caption=Информация->Фото');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * Изпълнява се след подготовка на Едит Формата
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        if(!$data->form->rec->id && ($code = Mode::get('catLastProductCode'))) {
            if ($newCode = str::increment($code)) {
            	
                //Проверяваме дали има такъв запис в системата
                if (!$mvc->fetch("#code = '$newCode'")) {
                    $data->form->rec->code = $newCode;
                }
            }
        }
    }
    
    
    /**
     * Изпълнява се след въвеждане на данните от Request
     */
    static function on_AfterInputEditForm($mvc, $form)
    {
        //Проверяваме за недопустими символи
        if ($form->isSubmitted()){
        	$rec = &$form->rec;
            if (preg_match('/[^0-9a-zа-я\- _]/iu', $rec->code)) {
                $form->setError('code', 'Полето може да съдържа само букви, цифри, тирета, интервали и долна черта!');
            }
           
        	foreach(array('eanCode', 'code') as $code) {
    			if($rec->$code) {
    				
    				// Проверяваме дали има продукт с такъв код (като изключим текущия)
	    			$check = $mvc->checkIfCodeExists($rec->$code);
	    			if($check && ($check->productId != $rec->id)
	    				|| ($check->productId == $rec->id && $check->packagingId != $rec->packagingId)) {
	    				$form->setError($code, 'Има вече артикул с такъв код!');
			        }
    			}
    		}
        }
                
        if (!$form->gotErrors()) {
            if(!$form->rec->id && ($code = Request::get('code', 'varchar'))) {
                Mode::setPermanent('catLastProductCode', $code);
            }    
        }
    }
    
    
    /**
     * Преди запис на продукт
     */
    public static function on_BeforeSave($mvc, $res, $rec)
    {
    	if(isset($rec->csv_measureId) && strlen($rec->csv_measureId) != 0){
    		$rec->measureId = cat_UoM::fetchField("#name = '{$rec->csv_measureId}'", "id");
    	}
    	
    	if(isset($rec->csv_groups) && strlen($rec->csv_groups) != 0){
    		$rec->groups = cat_Groups::getKeylistBySysIds($rec->csv_groups);
    	}
    	
    	if($rec->id){
    		// Старите мета данни
    		$rec->oldGroups = $mvc->fetchField($rec->id, 'groups');
    	}
    }
    
    
    /**
     * Извлича мета данните на продукт според групите
     * в които участва
     * @param mixed $groups - групи в които участва
     */
    private static function getMetaData($groups)
    {
    	if($groups){
    		$meta = array();
    		if(!is_array($groups)){
    			 $groups = keylist::toArray($groups);
    		}
		    foreach($groups as $grId){
		    	$grRec = cat_Groups::fetch($grId);
		    	if($grRec->meta){
		    		$arr = explode(",", $grRec->meta);
		    		$meta = array_merge($meta, array_combine($arr, $arr));
		    	}
		    }
		    
		    return implode(',', $meta);
    	}
    	
    	return '';
    }
    
    
    /**
     * Добавяне в таблицата на линк към детайли на продукта. Обр. на данните
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    static function on_AfterRecToVerbal ($mvc, $row, $rec, $fields = array())
    {
        if($fields['-single']) {
        	
        	// извличане на мета данните според групите
    		if($meta = $mvc->getMetaData($rec->groups)){
    			$Groups = cls::get(cat_Groups);
        		$row->meta = $Groups->fields['meta']->type->toVerbal($meta);
    		}
    		
            // fancybox ефект за картинките
            $Fancybox = cls::get('fancybox_Fancybox');
          
            $tArr = array(200, 150);
            $mArr = array(600, 450);
           
            $images_fields = array('image1',
                'image2',
                'image3',
                'image4',
                'image5');
            
            foreach ($images_fields as $image) {
                if ($rec->{$image} == '') {
                    $row->{$image} = NULL;
                } else {
                    $row->{$image} = $Fancybox->getImage($rec->{$image}, $tArr, $mArr);
                }
            }
            // ENDOF fancybox ефект за картинките
        }
    }
    
    
    /**
     * Оцветяване през ред
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        $rowCounter = 0;
        
        if (count($data->rows)) {
            foreach ($data->rows as $i=>&$row) {
                $rec = $data->recs[$i];
                $rowCounter++;
                $row->code = ht::createLink($row->code, array($mvc, 'single', $rec->id));
                $row->name = ht::createLink($row->name, array($mvc, 'single', $rec->id));
            }
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FNC('order', 'enum(alphabetic=Азбучно,last=Последно добавени)',
            'caption=Подредба,input,silent,remember');

        $data->listFilter->FNC('groupId', 'key(mvc=cat_Groups,select=name,allowEmpty)',
            'placeholder=Всички групи,caption=Група,input,silent,remember');
		
        $data->listFilter->FNC('meta', 'enum(all=Свойства,canSell=Продаваеми,
        						canBuy=Купуваеми,
        						canStore=Складируеми,
        						canConvert=Вложими,
        						fixedAsset=ДМА,
        						canManifacture=Производими)', 'input');
		
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->showFields = 'search,order,meta,groupId';
        $data->listFilter->input('order,groupId,search,meta', 'silent');
    }
    
    
    /**
     * Подредба и филтър на on_BeforePrepareListRecs()
     * Манипулации след подготвянето на основния пакет данни
     * предназначен за рендиране на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        // Подредба
        if($data->listFilter->rec->order == 'alphabetic' || !$data->listFilter->rec->order) {
            $data->query->orderBy('#name');
        } elseif($data->listFilter->rec->order == 'last') {
            $data->query->orderBy('#createdOn=DESC');
        }
        
        if ($data->listFilter->rec->groupId) {
            $data->query->where("#groups LIKE '|{$data->listFilter->rec->groupId}|'");
        }
        
        if ($data->listFilter->rec->meta && $data->listFilter->rec->meta != 'all') {
        	$groupIds = cat_Groups::getByMeta($data->listFilter->rec->meta);
        	$data->query->likeKeylist('groups', keylist::fromArray($groupIds));
        }
    }


    /**
     * Перо в номенклатурите, съответстващо на този продукт
     *
     * Част от интерфейса: acc_RegisterIntf
     */
    static function getItemRec($objectId)
    {
        $result = NULL;
        
        if ($rec = self::fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->code,
                'title' => $rec->name,
                'uomId' => $rec->measureId,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        if ($rec = self::fetch($objectId)) {
            $result = ht::createLink(static::getVerbal($rec, 'name'), array(__CLASS__, 'Single', $objectId));
        } else {
            $result = '<i>' .tr('неизвестно') . '</i>';
        }
        
        return $result;
    }
    
    
    /**
     * @see acc_RegisterIntf::itemInUse()
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
    }
    
    
    /**
     * Връща масив от продукти отговарящи на зададени мета данни:
     * canSell, canBuy, canManifacture, canConvert, fixedAsset, canStore
     * @param mixed $properties - комбинация на горе посочените мета 
     * 							  данни или като масив или като стринг
     * @return array $products - продукти отговарящи на условието, ако не са
     * 							 зададени мета данни връща всички продукти
     */
    public static function getByProperty($properties)
    {
    	$products = array();
    	$metaArr = arr::make($properties);
    	
    	if(!$allProducts = core_Cache::get('cat_Products', "productsMeta")){
    		$allProducts = static::cacheMetaData();
    	}
    	
    	if(count($metaArr)){
    		foreach ($metaArr as $meta){
    			$products = $products + $allProducts[$meta];
    		}
    	}
    	
    	return $products;
    }
    
    
    /**
     * Метод връщаш информация за продукта и неговите опаковки
     * @param int $productId - ид на продукта
     * @param int $packagingId - ид на опаковката, по дефолт NULL
     * @return stdClass $res
     * 	-> productRec - записа на продукта
     * 	-> meta - мета данни за продукта ако има
	 * 	     meta['canSell'] 		- дали може да се продава
	 * 	     meta['canBuy']         - дали може да се купува
	 * 	     meta['canConvert']     - дали може да се влага
	 * 	     meta['canStore']       - дали може да се съхранява
	 * 	     meta['canManifacture'] - дали може да се прозивежда
	 * 	     meta['fixedAsset']     - дали е ДМА
     * 	-> packagingRec - записа на опаковката, ако е зададена
     * 	-> packagings - всички опаковки на продукта, ако не е зададена
     */					
    public static function getProductInfo($productId, $packagingId = NULL)
    {
    	// Ако няма такъв продукт връщаме NULL
    	if(!$productRec = static::fetch($productId)) {
    		return NULL;
    	}
    	
    	$res = new stdClass();
    	$res->productRec = $productRec;
    	
    	// Добавяне на мета данните за продукта
    	if($meta = explode(',', static::getMetaData($productRec->groups))){
	    	foreach($meta as $value){
	    		$res->meta[$value] = TRUE;
	    	}
    	} else {
    		$res->meta = FALSE;
    	}
    	
    	$Packagings = cls::get('cat_products_Packagings');
    	if(!$packagingId) {
    		$res->packagings = array();
    		
    	    // Ако не е зададена опаковка намираме всички опаковки
    		$packagings = $Packagings->fetchDetails($productId);
    		
    		// Пре-индексираме масива с опаковки - ключ става id на опаковката 
    		foreach ((array)$packagings as $pack) {
    		    $res->packagings[$pack->packagingId] = $pack;
    		}
    	} else {
    		
    		// Ако е зададена опаковка, извличаме само нейния запис
    		$res->packagingRec = $Packagings->fetchPackaging($productId, $packagingId);
    		if(!$res->packagingRec) {
    			
    			// Ако я няма зададената опаковка за този продукт
    			return NULL;
    		}
    	}
    	
    	// Връщаме информацията за продукта
    	return $res;
    }
    
    
    /**
     * Връща ид на продукта и неговата опаковка по зададен Код/Баркод
     * @param mixed $code - Код/Баркод на търсения продукт
     * @return stdClass $res - Информация за намерения продукт
     * и неговата опаковка
     */
    public static function getByCode($code)
    {
    	$code = trim($code);
    	expect($code, 'Не е зададен код');
    	$res = new stdClass();
    	
    	// Проверяваме имали опаковка с този код: вътрешен или баркод
    	$Packagings = cls::get('cat_products_Packagings');
    	$catPack = $Packagings->fetchByCode($code);
    	if($catPack) {
    		
    		// Ако има запис намираме ид-та на продукта и опаковката
    		$res->productId = $catPack->productId;
    		$res->packagingId = $catPack->packagingId;
    	} else {
    		
    		// Проверяваме имали продукт с такъв код
    		$query = static::getQuery();
    		$query->where(array("#code = '[#1#]'", $code));
    		$query->orWhere(array("#eanCode = '[#1#]'", $code));
    		if($rec = $query->fetch()) {
    			
    			$res->productId = $rec->id;
    			$res->packagingId = NULL;
    		} else {
    			
    			// Ако няма продукт
    			return FALSE;
    		}
    	}
    	
    	return $res;
    }
    
    
    /**
     *  Проверява дали съществува продукт с такъв код,
     *  Кода и ЕАН-то на продукта както и тези на опаковките им
     *  трябва да са уникални
     *  @param string $code - Код/Баркод на продукт
     *  @return boolean int/FALSE - id на продукта с такъв код или
     *  FALSE ако няма такъв продукт
     */
    function checkIfCodeExists($code)
    {
    	if($info = cat_Products::getByCode($code)) {
    		return $info;
    	} else {
    		return FALSE;
    	}
    }
    
    
    /**
     * Връща всички продукти които са в посочените групи/група 
     * зададени, чрез техни systemId-та
     * @param mixed $group - sysId (стринг) или масив от sysId-та на групи
     * @return array $result - Продукти отговарящи на посочената група/групи
     */
    public static function getByGroup($group)
    {
    	if(!is_array($group)){
    		$group = array($group);
    	}
    	
    	$result = array();
    	$query = static::getQuery();
    	$groupIds = cat_Groups::getKeylistBySysIds($group);
    	$query->likeKeylist('groups', $groupIds, TRUE);
    	
    	while($rec = $query->fetch()){
	    	$result[$rec->id] = static::getTitleById($rec->id);
	    }
	    
	    return $result;
    }
    
    
    /**
     * Връща ДДС на даден продукт
     * @param int $productId - Ид на продукт
     * @param date $date - Дата към която начисляваме ДДС-то
     * @return double $vat - ДДС-то на продукта:
     * Ако има параметър ДДС за продукта го връщаме, впротивен случай
     * връщаме ДДС-то от периода
     * 		
     */
    public static function getVat($productId, $date = NULL)
    {
    	expect(static::fetch($productId), 'Няма такъв артикул');
    	
    	if(!$date){
    		$date = dt::now();
    	}
    	
    	// Ако има фиксиран параметър "ДДС" го връщаме
    	if($value = cat_products_Params::fetchParamValue($productId, 'vat')){
    		return $value;
    	}
    	
    	// Връщаме ДДС-то от периода
    	$period = acc_Periods::fetchByDate($date);
    	
    	return $period->vatRate;
    }
    
    
	/**
     * След всеки запис
     */
    static function on_AfterSave($mvc, &$id, $rec, $saveFileds = NULL)
    {
        if($rec->groups) {
            $mvc->updateGroupsCnt = TRUE;
        }
        
    	if($rec->oldGroups != $rec->groups) {
        	
        	// Ако има промяна на групите, Инвалидира се кеша
            core_Cache::remove('cat_Products', "productsMeta");
        }
    }
    
    
	/**
     * Рутинни действия, които трябва да се изпълнят в момента преди терминиране на скрипта
     */
    static function on_Shutdown($mvc)
    {
        if($mvc->updateGroupsCnt) {
            $mvc->updateGroupsCnt();
        }
    }
    
    
    /**
     * Ъпдейтване на броя продукти на всички групи
     */
    private function updateGroupsCnt()
    {
    	$groupsCnt = array();
    	$query = $this->getQuery();
        
        while($rec = $query->fetch()) {
            $keyArr = keylist::toArray($rec->groups);
            foreach($keyArr as $groupId) {
                $groupsCnt[$groupId]++;
            }
        }
        
        $groupQuery = cat_Groups::getQuery();
        while($grRec = $groupQuery->fetch()){
        	$grRec->productCnt = (int)$groupsCnt[$grRec->id];
        	cat_Groups::save($grRec);
        }
    }
    
    
    /**
     * Подготовка за рендиране на единичния изглед
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
        // Ако не е зададено файл
        if (!$fileHnd = $data->rec->photo) {
            
            // Вземаме файла от прикачените файлове на детайла
            $fileHnd = cat_products_Files::getImgFh($data->rec->id);
        }
        
        // Ако има манипулатор на файл
        if ($fileHnd) {
            
            // Fancy ефект за картинката
            $Fancybox = cls::get('fancybox_Fancybox');
            
            // Размер на thumbnail' а
            $tArr = array(200, 150);
            
            // Максималния размер на изображението
            $mArr = array(600, 450);
            
            // Вземаме тумбнаил на файла
            $data->row->image = $Fancybox->getImage($fileHnd, $tArr, $mArr);
        }
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$file = "cat/csv/Products.csv";
    	$fields = array( 
	    	0 => "name", 
	    	1 => "code", 
	    	2 => "csv_measureId", 
	    	3 => "csv_groups",);
    	
    	$cntObj = csv_Lib::importOnce($mvc, $file, $fields);
    	$res .= $cntObj->html;
    	
    	return $res;
    }
    
    
    /**
     * Връща продуктите, които могат да се продават на посочения клиент
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    public function getProducts($customerClass, $customerId, $datetime = NULL)
    {
    	return static::getByProperty('canSell');
    }
    
    
    /**
     * По кои политики се намира цената на продукта
     */
    public function getPolicies()
    {
    	return array('price_ListToCustomers', 'sales_SalesLastPricePolicy');
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * спрямо посочените ценови политики, Връща цената с най-голям
     * приоритет от намерените
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
    {
    	$prices = array();
    	$policies = $this->getPolicies();
    	foreach($policies as $name){
    		$Policy = cls::get($name);
    		$price = $Policy->getPriceInfo($customerClass, $customerId, $productId, $productManId, $packagingId, $quantity, $datetime);
    		if($price->price){
    			$prices[] = $price;
    		}
    	}
    	
    	if(!count($prices)){
    		return NULL;
    	} else {
    		
    		// Сортиране на намерените цени по техния приоритет
    		arr::order($prices, $field = 'priority', 'DESC');
    		
    		// Връща се цената с най-голям приоритет
    		return $prices[0];
    	}
    }
    
    
	/**
     * Връща масив със всички опаковки, в които може да участва един продукт
     */
    public static function getPacks($productId)
    {
    	$options = array('' => '');
    	
    	$query = cat_products_Packagings::getQuery();
    	$query->where("#productId = {$productId}");
    	$query->show("packagingId");
    	while($rec = $query->fetch()){
    		$options[$rec->packagingId] = cat_Packagings::getTitleById($rec->packagingId);
    	}
    	
    	return $options;
    }
    
    
    /**
     * Ъпдейтва мета данните на всички продукти
     * участващи в дадена група
     * @param int $groupId - ид на променената група
     */
    public static function updateMetaData($groupId)
    {
     	// За всички продукти участващи в групата
    	$query = static::getQuery();
     	$query->like('groups', "|{$groupId}|");
     	while($rec = $query->fetch()){
     		
     		// Презаписване на продукта, което ще преизчисли мета данните му
     		static::save($rec);
     	}
    }
    
    
    /**
     * Записва в кеша продуктите групирани по техните мета данни
     */
    public static function cacheMetaData()
    {
    	$cache = array();
    	$tmp = array();
    	$metas = array('canSell', 'canBuy', 'canStore', 'canConvert', 'fixedAsset', 'canManifacture');
    	foreach($metas as $meta){
    		$catGroups = cat_Groups::getByMeta($meta);
    		$keylist = keylist::fromArray($catGroups);
    		
    		$products = array();
    		$query = static::getQuery();
	    	$query->likeKeylist('groups', $keylist);
	    	$query->where("#state != 'rejected'");
	    	
	    	while($rec = $query->fetch()){
	    		if(!array_key_exists($rec->id, $tmp)){
	    			$tmp[$rec->id] = static::getTitleById($rec->id);
	    		}
	    		
	    		$products[$rec->id] = $tmp[$rec->id];
	    	}
	    	
	    	$cache[$meta] = $products;
    	}
    	
    	core_Cache::set('cat_Products', "productsMeta", $cache, 2880, array('cat_Products'));
    	
    	return $cache;
    }
}
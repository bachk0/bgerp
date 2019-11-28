<?php


/**
 * Контролер на терминала за пос продажби
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_Terminal extends peripheral_Terminal
{
    /**
     * Заглавие
     */
    public $title = 'ПОС Терминал';
    
    
    /**
     * Име на източника
     */
    protected $clsName = 'pos_Points';
    
    
    /**
     * Полета
     */
    protected $fieldArr = array('payments', 'policyId', 'caseId', 'storeId');
    
    
    protected static $operationsArr = "add=Артикул,payment=Плащане,quantity=Количество,price=Цена,discount=Отстъпка,text=Текст,contragent=Клиент,receipts=Бележки,revert=Сторно";
    
    //,packaging=Опаковка,quantity=Количество,price=Цена,discount=Отстъпка,text=Текст,client=Клиент,sale=Продажба,payment=Плащане,revert=Сторниране,nullify=Анулиране,receipts=Бележки
    
    protected static $forbiddenOperationOnEmptyReceipts = array('discount', 'price', 'text', 'quantity', 'payment');
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('payments', 'keylist(mvc=cond_Payments, select=title)', 'caption=Безналични начини на плащане->Позволени,placeholder=Всички');
        $fieldset->FLD('policyId', 'key(mvc=price_Lists, select=title)', 'caption=Политика, silent, mandotory');
        $fieldset->FLD('caseId', 'key(mvc=cash_Cases, select=name)', 'caption=Каса, mandatory');
        $fieldset->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад, mandatory');
    }
    
    
    /**
     * След подготовка на формата за добавяне
     *
     * @param core_Fieldset $fieldset
     */
    protected static function on_AfterPrepareEditForm($Driver, embed_Manager $Embedder, &$data)
    {
        $data->form->setDefault('policyId', cat_Setup::get('DEFAULT_PRICELIST'));
    }
    
    
    /**
     * Редиректва към посочения терминал в посочената точка и за посочения потребител
     *
     * @return Redirect
     *
     * @see peripheral_TerminalIntf
     */
    public function getTerminalUrl($pointId)
    {
        return array('pos_Points', 'openTerminal', $pointId);
    }
    
    public function act_Open()
    {
        
        $Receipts = cls::get('pos_Receipts');
        
        $Receipts->requireRightFor('terminal');
        expect($id = Request::get('receiptId', 'int'));
        expect($rec = $Receipts->fetch($id));
        
        // Имаме ли достъп до терминала
        if (!$Receipts->haveRightFor('terminal', $rec)) {
            
            return new Redirect(array($Receipts, 'new'));
        }
        
        // Лейаут на терминала
        $tpl = getTplFromFile('pos/tpl/terminal/Layout2.shtml');
        
        $tpl->replace(pos_Points::getTitleById($rec->pointId), 'PAGE_TITLE');
        $tpl->appendOnce("\n<link  rel=\"shortcut icon\" href=" . sbf('img/16/cash-register.png', '"', true) . '>', 'HEAD');
        $img = ht::createImg(array('path' => 'img/16/logout.png'));
        
        // Добавяме бележката в изгледа
        $receiptTpl = $this->getReceipt($rec);
        
        $tpl->replace($receiptTpl, 'RECEIPT');
        $tpl->replace(ht::createLink($img, array('core_Users', 'logout', 'ret_url' => true), false, 'title=Излизане от системата'), 'EXIT_TERMINAL');
        
        // Ако не сме в принтиране, сменяме обвивквата и рендираме табовете
        if (!Mode::is('printing')) {
            
            // Задаваме празна обвивка
            Mode::set('wrapper', 'page_Empty');
            
            // Ако сме чернова, добавяме пултовете
            if ($rec->state == 'draft') {
                
                $defaultOperation = Mode::get("currentOperation") ? Mode::get("currentOperation") : 'quantity';
                $defaultSearchString = Mode::get("currentSearchString");
                
                // Добавяне на табовете под бележката
                $toolsTpl = $this->getCommandPanel($rec, $defaultOperation);
                $tpl->replace($toolsTpl, 'TAB_TOOLS');
                
                // Добавяне на табовете показващи се в широк изглед отстрани
                $lastRecId = pos_ReceiptDetails::getLastProductRecId($rec->id);
                $resultTabHtml = $this->renderResult($rec, $defaultOperation, $defaultSearchString, $lastRecId);
                $tpl->append($resultTabHtml, 'SEARCH_RESULT');
            }
        }
        
        $data = (object) array('rec' => $rec);
        
        $this->invoke('AfterRenderSingle', array(&$tpl, $data));
        if (!Mode::is('printing')) {
            $tpl->append("<iframe name='iframe_a' style='display:none'></iframe>");
        }
        
        // Вкарване на css и js файлове
        $this->pushTerminalFiles($tpl);
        $this->renderWrapping($tpl);
        
        return $tpl;
    }
    
    public function getCommandPanel($rec)
    {
        $Receipts = cls::get('pos_Receipts');
        expect($rec = $Receipts->fetchRec($rec));
        
        $block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('TAB_TOOLS');
        $operation = Mode::get("currentOperation");
        $keyupUrl = null;
        
        switch($operation){
            case 'add':
                $inputUrl = array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $rec->id);
                $keyupUrl = array($this, 'displayOperation', 'receiptId' => $rec->id);
                break;
            case 'quantity':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setquantity');
                break;
            case 'discount':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setdiscount');
                break;
            case 'price':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setprice');
                break;
            case 'text':
                $inputUrl = array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'settext');
                break;
            case 'payment';
                break;
            case 'contragent';
                $keyupUrl = array($this, 'displayOperation', 'receiptId' => $rec->id);
                break;
            case 'revert';
                $keyupUrl = array($this, 'displayOperation', 'receiptId' => $rec->id);
                break;
        }
        
        if(is_array($inputUrl)){
            $inputUrl = toUrl($inputUrl, 'local');
        }
        
        if(is_array($keyupUrl)){
            $keyupUrl = toUrl($keyupUrl, 'local');
        }
        
        // Ако можем да добавяме към бележката
        if ($Receipts->pos_ReceiptDetails->haveRightFor('add', (object) array('receiptId' => $rec->id))) {
            $modQUrl = toUrl(array('pos_ReceiptDetails', 'setQuantity'), 'local');
            $discUrl = toUrl(array('pos_ReceiptDetails', 'setDiscount'), 'local');
            
            $doActionUrl = toUrl(array($this, 'setoperation', 'receiptId' => $rec->id), 'local');
        } else {
            $discUrl = $modQUrl = $doActionUrl = null;
            $disClass = 'disabledBtn';
            $disabled = 'disabled';
        }
        
        $value = null;
        
        $browserInfo = Mode::get('getUserAgent');
        if (stripos($browserInfo, 'Android') !== false) {
            //$htmlScan = "<input type='button' class='webScan {$disClass}' {$disabled} id='webScan' name='scan' onclick=\"document.location = 'http://zxing.appspot.com/scan?ret={$absUrl}?ean={CODE}'\" value='Scan' />";
            //$block->append($htmlScan, 'FIRST_TOOLS_ROW');
        }
        
        $value = round(abs($rec->total) - abs($rec->paid), 2);
        $value = ($value > 0) ? $value : null;
        
        //bp($operation, $rec);
        $inputValue = ($operation == 'payment') ? $value : Mode::get("currentSearchString");
        
        $searchUrl = toUrl(array($this, 'displayOperation', 'receiptId' => $rec->id), 'local');
        $params = array('name' => 'ean', 'value' => $inputValue, 'type' => 'text', 'class'=> 'large-field select-input-pos', 'data-url' => $inputUrl, 'data-keyupurl' => $keyupUrl, 'title' => 'Въвеждане', 'list' => 'suggestions');
        if(Mode::is('screenMode', 'narrow')) {
            $params['readonly'] = 'readonly';
        }
        
        // Показване на даталист на сторно бележката, с предложения на артикулите, които се срещат в оригинала
        if(isset($rec->revertId)){
            $dQuery = pos_ReceiptDetails::getQuery();
            $dQuery->where(array('#receiptId = [#1#]', $rec->revertId));
            $dQuery->where('#productId IS NOT NULL');
            $datalist = "<datalist id='suggestions'>";
            while ($dRec = $dQuery->fetch()){
                $pCode = cat_Products::getVerbal($dRec->productId, 'code');
                $pName = cat_Products::getTitleById($dRec->productId, false);
                $datalist .= "<option data-value = '{$pCode}' value='{$pName}'>";
            }
            $datalist .= "</datalist>";
            $block->append($datalist, 'INPUT_DATA_LIST');
        }
        
        $operations = arr::make(self::$operationsArr);
        if (pos_Setup::get('SHOW_DISCOUNT_BTN') != 'yes') {
            unset($operations['discount']);
        }
        
        $detailsCount = pos_ReceiptDetails::count("#receiptId = {$rec->id}");
        if(empty($detailsCount)){
            foreach (self::$forbiddenOperationOnEmptyReceipts as $operationToRemove){
                unset($operations[$operationToRemove]);
            }
        }
        
        $currentOperation = Mode::get("currentOperation");
        if(Mode::is('screenMode', 'narrow')){
            $operationSelectFld = ht::createSelect('operation', $operations, $currentOperation, array('class' => '', 'data-url' => $searchUrl));
            $block->append($operationSelectFld, 'INPUT_FLD');
        } else {
            foreach ($operations as $operation => $operationCaption){
                $class = 'operationBtn';
                if($operation == $currentOperation){
                    $class .= " selected";
                }
                $btn = ht::createFnBtn($operationCaption, '', '', array('data-url' => $searchUrl, 'class' => $class, 'data-value' => $operation));
                $block->append($btn, 'INPUT_FLD');
            }
        }
        
        $block->append(ht::createElement('input', $params), 'INPUT_FLD');
        $block->append(ht::createElement('input', array('name' => 'receiptId', 'type' => 'hidden', 'value' => $rec->id)), 'INPUT_FLD');
        $block->append(ht::createElement('input', array('name' => 'rowId', 'type' => 'hidden', 'value' => $value)), 'INPUT_FLD');
        
        $block->append($this->renderKeyboard('tools'), 'KEYBOARDS');
        
        return $block;
    }
    
    
    function act_displayOperation()
    {
        expect($id = Request::get('receiptId', 'int'));
        expect($rec = pos_Receipts::fetch($id));
        expect($operation = Request::get('operation', "enum(" . self::$operationsArr . ")"));
        $selectedRecId = Request::get('recId', 'int');
        
        
        $string = Request::get('search', 'varchar');
        Mode::setPermanent("currentOperation", $operation);
        Mode::setPermanent("currentSearchString", $string);
        
        return static::returnAjaxResponse($rec, $selectedRecId, true);
    }
    
    
    public function renderResult($rec, $currOperation, $string, $selectedRecId = null)
    {
        $detailsCount = pos_ReceiptDetails::count("#receiptId = {$rec->id}");
        if(empty($detailsCount) && in_array($currOperation, static::$forbiddenOperationOnEmptyReceipts)){
            
            return new core_ET();
        }
        
        $string = trim($string);
        
        switch($currOperation){
            case 'add':
                $res = (empty($string)) ? $this->getFavouritesBtns() : $this->getProductResultTable($rec, $string);
                break;
            case 'receipts':
                $res = $this->renderDraftsTab($rec);
                break;
            case 'quantity':
                $res = $this->renderPackagingTable($rec, $string, $selectedRecId);
                break;
            case 'discount':
                $res = ' ';
            case 'text':
                $res = ' ';
                break;
            case 'price':
                $res = $this->renderLastPriceTable($rec, $string, $selectedRecId);
                break;
                break;
            case 'payment':
                $res = $this->renderPaymentTabs($rec, $string, $selectedRecId);
                break;
            case 'revert':
                $res = $this->renderRevertTable($rec, $string, $selectedRecId);
                break;
            case 'contragent':
                $res = $this->renderContragentTable($rec, $string, $selectedRecId);
                break;
            default:
                $res = "{$currOperation} '$string' {$selectedRecId} @TODO";
                break;
        }
        
        return new core_ET($res);
    }
    
    public function renderContragentTable($rec, $string, $selectedRecId)
    {
        $contragents = array();
        
        $stringInput = core_Type::getByName('varchar')->fromVerbal($string);
        if($cardRec = crm_ext_Cards::fetch("#number = '{$stringInput}'")){
            $contragents["{$cardRec->contragentClassId}|{$cardRec->contragentId}"] = (object)array('contragentClassId' => $cardRec->contragentClassId, 'contragentId' => $cardRec->contragentId, 'title' => cls::get($cardRec)->getHyperlink($cardRec->contragentId, true));
        }
        
        $personClassId = crm_Persons::getClassId();
        $companyClassId = crm_Companies::getClassId();
        
        $cQuery = crm_Companies::getQuery();
        $cQuery->fetch("#vatId = '{$stringInput}' OR #uicId = '{$stringInput}'");
        $cQuery->show('id');
        while($cRec = $cQuery->fetch()){
            $contragents["{$companyClassId}|{$cRec->id}"] = (object)array('contragentClassId' => crm_Companies::getClassId(), 'contragentId' => $cRec->id, 'title' => crm_Companies::getShortHyperlink($cRec->id, true));
        }
        
        $pQuery = crm_Persons::getQuery();
        $pQuery->fetch("#egn = '{$stringInput}' OR #vatId = '{$stringInput}'");
        $pQuery->show('id');
        while($pRec = $pQuery->fetch()){
            $contragents["{$personClassId}|{$pRec->id}"] = (object)array('contragentClassId' => crm_Persons::getClassId(), 'contragentId' => $pRec->id, 'title' => crm_Persons::getShortHyperlink($pRec->id, true));
        }
        
        foreach (array('crm_Companies', 'crm_Persons') as $ContragentClass){
            $cQuery = $ContragentClass::getQuery();
            $stringInput = plg_Search::normalizeText($stringInput);
            plg_Search::applySearch($stringInput, $cQuery);
            
            $cQuery->where("#state != 'rejected' AND #state != 'closed'");
            $cQuery->show('id');
            
            $classId = ($ContragentClass == 'crm_Companies') ? $companyClassId : $personClassId;
            while($cRec = $cQuery->fetch()){
                if(!array_key_exists("{$classId}|{$cRec->id}", $contragents)){
                    $contragents["{$classId}|{$cRec->id}"] = (object)array('contragentClassId' => $ContragentClass::getClassId(), 'contragentId' => $cRec->id, 'title' => $ContragentClass::getShortHyperlink($cRec->id, true));
                }
                
                if(count($contragents) > 20) break;
            }
        }
        
        $canTransfer = pos_Receipts::haveRightFor('transfer', $rec);
        $tpl = new core_ET("");
        $cnt = 0;
        foreach ($contragents as $obj){
            $class = ($cnt == 0) ? 'posResultContragent navigable selected' : 'posResultContragent navigable';
            $transferUrl = ($canTransfer === true) ? array('pos_Receipts', 'Transfer', 'id' => $rec->id, 'contragentClassId' => $obj->contragentClassId, 'contragentId' => $obj->contragentId) : array();
            $obj->transferBtn = ht::createBtn('Прехвърли', $transferUrl, false, false, "class=transferBtn,title=Прехвърли продажбата към контрагента");
            
            $block = new core_ET("<div class='{$class}'><div>[#transferBtn#]</div><div class='posResultContragentTitle'>[#title#]</div></div>");
            $block->placeObject($obj);
            $block->removeBlocksAndPlaces();
            
            $tpl->append($block);
            $cnt++;
        }
        
        return $tpl;
    }
    
    public static function renderRevertTable($rec, $string, $selectedRecId)
    {
        $Receipts = cls::get('pos_Receipts');
        $string = plg_Search::normalizeText($string);
        
        
        $query = $Receipts->getQuery();
        $query->where("#revertId IS NULL AND #state != 'draft' AND #pointId = {$rec->pointId}");
        
       
        //$foundArr = $Receipts->findReceiptByNumber($string, true);
        
        if (is_object($foundArr['rec'])) {
            $query->where(array("#id = {$foundArr['rec']->id}"));
        } else {
            $query->where(array("#searchKeywords LIKE '%[#1#]%'", $string));
        }
        
        $buttons = array();
        $cnt = 0;
        while($receiptRec = $query->fetch()){
            $btnTitle = pos_Receipts::getTitleById($receiptRec);
            $class = ($cnt == 0) ? "navigable selected" : "navigable";
            
            $buttons[] = ht::createLink($btnTitle, array('pos_Receipts', 'revert', $receiptRec->id, 'ret_url' => true), 'Наистина ли желаете да сторнирате бележката|*?', "ef_icon=img/16/red-back.png,title=Сторниране на бележката,class={$class}");
            $cnt++;
        }
        
        $tpl = new core_ET("");
        foreach ($buttons as $btn){
            $tpl->append($btn);
        }
        
        return $tpl;
    }
    
    public static function renderPaymentTabs($rec, $string, $selectedRecId)
    {
        $Receipts = cls::get('pos_Receipts');
        $tpl = new core_ET("");
        
        $payUrl = array();
        if (pos_Receipts::haveRightFor('pay', $rec)) {
            $payUrl = toUrl(array('pos_ReceiptDetails', 'makePayment', 'receiptId' => $rec->id), 'local');
        }
        
        // Показваме всички активни методи за плащания
        $disClass = ($payUrl) ? '' : 'disabledBtn';
        
        $element = ht::createElement("div", array('class' => "{$disClass} navigable payment selected", 'data-type' => '-1', 'data-url' => $payUrl), tr('В брой'), true);
        $tpl->append($element);
        
        $payments = pos_Points::fetchSelected($rec->pointId);
        foreach ($payments as $paymentId => $paymentTitle){
            $element = ht::createElement("div", array('class' => "{$disClass} navigable payment", 'data-type' => $paymentId, 'data-url' => $payUrl), tr($paymentTitle), true);
            $tpl->append($element);
        }
        
        $buttons = $Receipts->getPaymentTabBtns($rec);
        foreach ($buttons as $btn){
            $tpl->append($btn);
        }
        
        return $tpl;
        
    }
    
    public function renderPackagingTable($rec, $string, $selectedRecId)
    {
        $selectedRec = pos_ReceiptDetails::fetch($selectedRecId);
        $measureId = cat_Products::fetchField($selectedRec->productId, 'measureId');
        
        $packs = cat_Products::getPacks($selectedRec->productId);
        $basePackagingId = key($packs);
        
        $baseClass = "resultPack navigable";
        $basePackName = cat_UoM::getTitleById($measureId);
        $dataUrl = (pos_ReceiptDetails::haveRightFor('edit', $selectedRec)) ? toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setquantity'), 'local') : null;
        
        $buttons = array();
        $class = ($measureId == $basePackagingId) ? "{$baseClass} selected" : $baseClass;
        $buttons[$measureId] = ht::createElement("div", array('class' => $class, 'data-pack' => $basePackName, 'data-url' => $dataUrl), tr($basePackName), true);
       
        $packQuery = cat_products_Packagings::getQuery();
        $packQuery->where("#productId = {$selectedRec->productId}");
        while ($packRec = $packQuery->fetch()) {
            
            $packagingId = cat_UoM::getTitleById($packRec->packagingId);
            $baseMeasureId = $measureId;
            $packRec->quantity = cat_Uom::round($baseMeasureId, $packRec->quantity);
            $packaging = "|{$packagingId}|* (" . core_Type::getByName('double(smartRound)')->toVerbal($packRec->quantity) . " " . cat_UoM::getTitleById($baseMeasureId) . ")";
            
            $class = ($packRec->packagingId == $basePackagingId) ? "{$baseClass} selected" : $baseClass;
            $buttons[$packRec->packagingId] = ht::createElement("div", array('class' => $class, 'data-pack' => $packagingId, 'data-url' => $dataUrl), tr($packaging), true);
        }
        
        $firstBtn = $buttons[$basePackagingId];
        unset($buttons[$basePackagingId]);
        $buttons = array($basePackagingId => $firstBtn) + $buttons;
        
        $tpl = new core_ET("");
        foreach ($buttons as $btn){
            $tpl->append($btn);
        }
        
        return $tpl;
        
    }
    
    
    public function renderLastPriceTable($rec, $string, $selectedRecId)
    {
        $selectedRec = pos_ReceiptDetails::fetch($selectedRecId);
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode();
        $buttons = array();
        
        $dQuery = pos_ReceiptDetails::getQuery();
        $dQuery->where("#action = 'sale|code' AND #productId = {$selectedRec->productId} AND #quantity > 0");
        $dQuery->orderBy('id', 'desc');
        if(isset($selectedRec->value)){
            $dQuery->where("#value = {$selectedRec->value}"); 
            $value = $selectedRec->value;
        } else {
            $dQuery->where("#value IS NULL");
            $value = cat_Products::fetchField($selectedRec->productId, 'measureId');
        }
        
        $cnt = 0;
        $packName = cat_UoM::getShortName($value);
        $dQuery->show('price,param');
        while($dRec = $dQuery->fetch()){
            $dRec->price *= 1 + $dRec->param;
            Mode::push('text', 'plain');
            $price = core_Type::getByName('double(smartRound)')->toVerbal($dRec->price);
            Mode::pop('text', 'plain');
            $btnName = "|*{$price} {$baseCurrencyCode} |" . tr($packName);
            $dataUrl = toUrl(array('pos_ReceiptDetails', 'updaterec', 'receiptId' => $rec->id, 'action' => 'setprice', 'string' => $price), 'local');
            
            
            $class = ($cnt == 0) ? 'resultPrice navigable selected' : 'resultPrice navigable';
            $buttons[$dRec->price] = ht::createElement("div", array('class' => $class, 'data-url' => $dataUrl), tr($btnName), true);
        }
        
        $tpl = new core_ET("");
        foreach ($buttons as $btn){
            $tpl->append($btn);
        }
        
        return $tpl;
    }
    
    
    /**
     * Рендира бързите бутони
     *
     * @return core_ET $block - шаблон
     */
    public function getFavouritesBtns()
    {
        $products = pos_Favourites::prepareProducts();
        if (!$products->arr) {
            
            return false;
        }
        
        $tpl = pos_Favourites::renderPosProducts($products);
        
        return $tpl;
    }
    
    
    
    /**
     * Рендира клавиатурата
     *
     * @return core_ET $tpl
     */
    public static function renderKeyboard($tab)
    {
        $tpl = new core_ET("");
        if(Mode::get('screenWidth') >= 1200){
            $tpl = getTplFromFile('pos/tpl/terminal/Keyboards.shtml');
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготовка и рендиране на бележка
     *
     * @param int $id - ид на бележка
     *
     * @return core_ET $tpl - шаблона
     */
    public function getReceipt_($id)
    {
        $Receipts = cls::get('pos_Receipts');
        expect($rec = $Receipts->fetchRec($id));
        
        $data = new stdClass();
        $data->rec = $rec;
        $this->prepareReceipt($data);
        $tpl = $this->renderReceipt($data);
        $Receipts->invoke('AfterGetReceipt', array(&$tpl, $rec));
        
        return $tpl;
    }
    
    
    /**
     * Подготовка на бележка
     */
    private function prepareReceipt(&$data)
    {
        $Receipt = cls::get('pos_Receipts');
        
        $fields = $Receipt->selectFields();
        $fields['-terminal'] = true;
        $data->row = $Receipt->recToverbal($data->rec, $fields);
        unset($data->row->contragentName);
        $data->receiptDetails = $Receipt->pos_ReceiptDetails->prepareReceiptDetails($data->rec->id);
        $data->receiptDetails->rec = $data->rec;
    }
    
    
    /**
     * Подготовка и рендиране на бележка
     *
     * @return core_ET $tpl - шаблон
     */
    private function renderReceipt($data)
    {
        $Receipt = cls::get('pos_Receipts');
        
        // Слагане на мастър данните
        if (!Mode::is('printing')) {
            $tpl = getTplFromFile('pos/tpl/terminal/Receipt.shtml');
        } else {
            $tpl = getTplFromFile('pos/tpl/terminal/ReceiptPrint.shtml');
        }
        
        $tpl->placeObject($data->row);
        $img = ht::createElement('img', array('src' => sbf('pos/img/bgerp.png', '')));
        $logo = ht::createLink($img, array('bgerp_Portal', 'Show'), null, array('target' => '_blank', 'class' => 'portalLink', 'title' => 'Към портала'));
        $tpl->append($logo, 'LOGO');
        
        if($lastRecId = pos_ReceiptDetails::getLastProductRecId($data->rec->id)){
            $data->receiptDetails->rows[$lastRecId]->CLASS = 'highlighted';
        }
        
        // Слагане на детайлите на бележката
        $detailsTpl = $Receipt->pos_ReceiptDetails->renderReceiptDetail($data->receiptDetails);
        $tpl->append($detailsTpl, 'DETAILS');
        
        return $tpl;
    }
    
    
    /**
     * Вкарване на css и js файлове
     */
    public function pushTerminalFiles_(&$tpl)
    {
        $tpl->push('css/Application.css', 'CSS');
        $tpl->push('css/default-theme.css', 'CSS');
        $tpl->push('pos/tpl/css/styles.css', 'CSS');
        if (!Mode::is('printing')) {
            $tpl->push('pos/js/scripts.js', 'JS');
            $tpl->push('https://cdn.jsdelivr.net/npm/naviboard@4.1.0/dist/naviboard.min.js', 'JS');
            jquery_Jquery::run($tpl, 'posActions();');
        }

        $conf = core_Packs::getConfig('fancybox');
        $tpl->push('fancybox/' . $conf->FANCYBOX_VERSION . '/jquery.fancybox.css', 'CSS');
        $tpl->push('fancybox/' . $conf->FANCYBOX_VERSION . '/jquery.fancybox.js', 'JS');
        jquery_Jquery::run($tpl, "$('a.fancybox').fancybox();", true);
    }
    
    
    /**
     * Връща таблицата с продукти отговарящи на определен стринг
     */
    public function getProductResultTable($rec, $string)
    {
        $searchString = plg_Search::normalizeText($string);
        $data = new stdClass();
        $data->rec = $rec;
        $data->searchString = $searchString;
        $data->baseCurrency = acc_Periods::getBaseCurrencyCode();
        
        $this->prepareProductTable($data);
        
        $tpl = new core_ET("");
        $block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('PRODUCTS_RESULT');
        foreach ($data->rows as $row){
            $bTpl = clone $block;
            $bTpl->placeObject($row);
            $bTpl->removeBlocksAndPlaces();
            $tpl->append($bTpl);
        }
        
        return $tpl;
    }
    
    
    /**
     * Подготвя данните от резултатите за търсене
     */
    private function prepareProductTable(&$data)
    {
        $data->rows = array();
        $count = 0;
        $conf = core_Packs::getConfig('pos');
        $data->showParams = $conf->POS_RESULT_PRODUCT_PARAMS;
        
        $folderId = cls::get($data->rec->contragentClass)->fetchField($data->rec->contragentObjectId, 'folderId');
        $pQuery = cat_Products::getQuery();
        $pQuery->where("#canSell = 'yes' AND #state = 'active'");
        $pQuery->where("#isPublic = 'yes' OR (#isPublic = 'no' AND #folderId = '{$folderId}')");
        $pQuery->where(array("#searchKeywords LIKE '%[#1#]%'", $data->searchString));
        $pQuery->show('id,name,isPublic,nameEn,code');
        $pQuery->limit($this->maxSearchProducts);
        $sellable = $pQuery->fetchAll();
        if (!count($sellable)) {
            
            return;
        }
        
        $Policy = cls::get('price_ListToCustomers');
        $Products = cls::get('cat_Products');
        
        foreach ($sellable as $id => $name) {
            $pInfo = cat_Products::getProductInfo($id);
            
            $packs = $Products->getPacks($id);
            $packId = key($packs);
            $perPack = (isset($pInfo->packagings[$packId])) ? $pInfo->packagings[$packId]->quantity : 1;
            
            $price = $Policy->getPriceInfo($data->rec->contragentClass, $data->rec->contragentObjectId, $id, $packId, 1, $data->rec->createdOn, 1, 'yes');
            
            // Ако няма цена също го пропускаме
            if (empty($price->price)) {
                continue;
            }
            $vat = $Products->getVat($id);
            $obj = (object) array('productId' => $id,
                'measureId' => $pInfo->productRec->measureId,
                'price' => $price->price * $perPack,
                'packagingId' => $packId,
                'vat' => $vat);
            
            $photo = cat_Products::getParams($id, 'preview');
            if (!empty($photo)) {
                $obj->photo = $photo;
            }
            
            if (isset($pInfo->meta['canStore'])) {
                $obj->stock = pos_Stocks::getQuantity($id, $data->rec->pointId);
                $obj->stock /= $perPack;
            }
            
            // Обръщаме реда във вербален вид
            $data->rows[$id] = $this->getVerbalSearchresult($obj, $data);
            $data->rows[$id]->CLASS = ' navigable';
            
            if($count == 0){
                $data->rows[$id]->CLASS .= ' selected';
            }
            $count++;

        //    if($count > 10) break;
        }
    }
    
    
    /**
     * Връща вербалното представяне на един ред от резултатите за търсене
     */
    private function getVerbalSearchResult($obj, &$data)
    {
        $Receipts = cls::get('pos_Receipts');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $row = new stdClass();
        
        $row->price = currency_Currencies::decorate($Double->toVerbal($obj->price));
        $row->stock = $Double->toVerbal($obj->stock);
        $row->packagingId = ($obj->packagingId) ? cat_UoM::getTitleById($obj->packagingId) : cat_UoM::getTitleById($obj->measureId);
        $row->packagingId = str::getPlural($obj->stock, $row->packagingId, true);
        
        $obj->receiptId = $data->rec->id;
        if ($Receipts->pos_ReceiptDetails->haveRightFor('add', $obj)) {
            $addUrl = toUrl(array('pos_ReceiptDetails', 'addProduct', 'receiptId' => $obj->receiptId), 'local');
        } else {
            $addUrl = null;
        }
        
        $row->productId = cat_Products::getTitleById($obj->productId);
        if ($data->showParams) {
            $params = keylist::toArray($data->showParams);
            foreach ($params as $pId) {
                if ($vRec = cat_products_Params::fetch("#productId = {$obj->productId} AND #paramId = {$pId}")) {
                    $row->productId .= ' &nbsp;' . cat_products_Params::recToVerbal($vRec, 'paramValue')->paramValue;
                }
            }
        }
        
        $attr = array('class' => 'pos-add-res-btn', 'data-url' => $addUrl, 'data-productId' => $obj->productId, 'title' => 'Добавете артикула към бележката');
        $row->productId = ht::createElement('span', $attr, $row->productId, true);
        $row->productId = ht::createLinkRef($row->productId, array('cat_Products', 'single', $obj->productId), null, array('target' => '_blank', 'class' => 'singleProd'));
        
        $row->stock = ht::styleNumber($row->stock, $obj->stock, 'green');
        $row->stock = "{$row->stock} <span class='pos-search-row-packagingid'>{$row->packagingId}</span>";
        $row->productId = "<span class='pos-search-row-productId'>{$row->productId}</span><span class='pos-search-row-stock'>{$row->stock}</span> ";
        
        if (!Mode::is('screenMode', 'narrow')) {
            if(!empty($obj->photo)){
                $Fancybox = cls::get('fancybox_Fancybox');
                $preview = $Fancybox->getImage($obj->photo, array('64', '64'), array('550', '550'));
                $row->photo = $preview;
            } else {
                $thumb = new thumb_Img(getFullPath('pos/img/default-image.jpg'), 64, 64, 'path');
                $arr = array();
                $row->photo = $thumb->createImg($arr);
            }
        }
        
        return $row;
    }
    
    
    /**
     * Рендиране на таба с черновите
     *
     * @param int $id -ид на бележка
     *
     * @return core_ET $block - шаблон
     */
    public function renderDraftsTab($id)
    {
        $rec = $this->fetchRec($id);
        $block = getTplFromFile('pos/tpl/terminal/ToolsForm.shtml')->getBlock('DRAFTS');
        $pointId = pos_Points::getCurrent('id');
        $now = dt::today();
        
        // Намираме всички чернови бележки и ги добавяме като линк
        $query = pos_Receipts::getQuery();
        $query->where("#state = 'draft' AND #pointId = '{$pointId}' AND #id != {$rec->id}");
        while ($rec = $query->fetch()) {
            $date = dt::mysql2verbal($rec->createdOn, 'H:i');
            $between = dt::daysBetween($now, $rec->valior);
            $between = ($between != 0) ? " <span class='num'>-${between}</span>" : null;
            
            $class = isset($rec->revertId) ? 'revert-receipt' : '';
            $row = ht::createLink("<span class='pos-span-name'>№{$rec->id} <br> {$date}$between</span>", array('pos_Terminal', 'open', 'receiptId' => $rec->id), null, array('class' => "pos-notes navigable {$class}", 'title' => 'Преглед на бележката'));
            $block->append($row);
        }
        
        if (pos_Receipts::haveRightFor('add')) {
            $addBtn = ht::createLink("<span class='pos-span-name'>" . tr('Нова') . '</span>', array('pos_Receipts', 'new', 'forced' => true), null, 'class=pos-notes navigable selected');
            $block->prepend($addBtn);
        }
        
        return $block;
    }
    
    
    
    
    public static function returnAjaxResponse($receiptId, $selectedRecId, $success, $refreshTable = false)
    {
        $me = cls::get(get_called_class());
        $Receipts = cls::get('pos_Receipts');
        $rec = $Receipts->fetchRec($receiptId);
        $operation = Mode::get("currentOperation");
        $string = Mode::get("currentSearchString");
        $res = array();
        
        if($success === true){
            $resultTpl = $me->renderResult($rec, $operation, $string, $selectedRecId);
            $toolsTpl = $me->getCommandPanel($rec, $operation, $string);
            
            // Ще се реплейсват резултатите
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'result-holder', 'html' => $resultTpl->getContent(), 'replace' => true);
            $res[] = $resObj;
            
            // Ще се реплейсва и пулта
            $resObj = new stdClass();
            $resObj->func = 'html';
            $resObj->arg = array('id' => 'tools-holder', 'html' => $toolsTpl->getContent(), 'replace' => true);
            $res[] = $resObj;
            
            $resObj = new stdClass();
            $resObj->func = 'fancybox';
            $res[] = $resObj;
            
            if($refreshTable === true){
                $receiptTpl = $me->getReceipt($rec);
                
                $resObj = new stdClass();
                $resObj->func = 'html';
                $resObj->arg = array('id' => 'receipt-table', 'html' => $receiptTpl->getContent(), 'replace' => true);
                $res[] = $resObj;
            }
        }
        
        // Показваме веднага и чакащите статуси
        $hitTime = Request::get('hitTime', 'int');
        $idleTime = Request::get('idleTime', 'int');
        $statusData = status_Messages::getStatusesData($hitTime, $idleTime);
        
        $res = array_merge($res, (array) $statusData);
        
        return $res;
    }
}

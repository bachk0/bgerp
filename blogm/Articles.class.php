<?php

/**
 * Статии
 *
 *
 * @category  bgerp
 * @package   blogm
 * @author    Ивелин Димов <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class blogm_Articles extends core_Master {
	
	
	/**
	 * Заглавие на страницата
	 */
	var $title = 'Блог статии';
	
	
	/**
	 * Тип на разрешените файлове за качване
	 */
	const FILE_BUCKET = 'blogmFiles';
	
	
	/**
	 * Зареждане на необходимите плъгини
	 */
	var $loadList = 'plg_RowTools, plg_State, plg_Printing, blogm_Wrapper, plg_Search, plg_Created, plg_Modified,plg_Rejected';
	
	
	/**
	 * Полета за листов изглед
	 */
	var $listFields ='id, title, categories, body, author, createdOn, createdBy, modifiedOn, modifiedBy, archiveId';
	
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    var $rowToolsSingleField = 'title';

    
	/**
	 *  Брой статии на страница 
	 */
	var $listItemsPerPage = "4";
	
	
	/**
	 * Коментари на статията
	 */
	var $details = 'blogm_Comments';
	
	
	/** 
	 *  Полета по които ще се търси
	 */
	var $searchFields = 'title, author, body';
	
	
	/**
	 * Кой може да листва статии и да чете  статия
	 */
	var $canRead = 'cms, ceo, admin';
	
	
	/**
	 * Кой може да добявя,редактира или изтрива статия
	 */
	var $canWrite = 'cms, ceo, admin';
	
	/**
	 * Кой може да вижда публичните статии
	 */
	var $canArticle = 'every_one';
	
	/**
	 * Файл за единичен изглед
	 */
	//var $singleLayoutFile = 'blogm/tpl/SingleArticle.shtml';


    /**
	 * Единично заглавие на документа
	 */
	var $singleTitle = 'Статия';
	
	
	/**
	 * Описание на модела
	 */
	function description()
	{
		$this->FLD('author', 'varchar(40)', 'caption=Автор, mandatory, notNull,width=100%');
		$this->FLD('title', 'varchar(190)', 'caption=Заглавие, mandatory, width=100%');
		$this->FLD('categories', 'keylist(mvc=blogm_Categories,select=title)', 'caption=Категории,mandatory');
		$this->FLD('body', 'richtext(bucket=' . self::FILE_BUCKET . ')', 'caption=Съдържание,mandatory');
 		$this->FLD('commentsMode', 
            'enum(enabled=Разрешени,confirmation=С потвърждение,disabled=Забранени,stopped=Спрени)',
            'caption=Коментари->Режим,maxRadio=4,columns=4,mandatory');
        $this->FLD('commentsCnt', 'int', 'caption=Коментари->Брой,value=0,notNul,input=none');
  		$this->FLD('state', 'enum(draft=Чернова,active=Публикувана,rejected=Оттеглена)', 'caption=Състояние,mandatory');
		$this->FLD('archiveId', 'key(mvc=blogm_Archives,select=title)', 'caption=Архив,mandatory');
        
		$this->setDbUnique('title');
	}
	
	
	/**
	 * Обработка на вербалното представяне на статиите
	 */
	function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
        if($fields['-browse']) { 
            $txt = explode("\n", $rec->body, 2);
            $rec->body = trim($txt[0]); 
            $rec->body .=   " [link=" . toUrl(array('blogm_Articles', 'Article', $rec->id), 'absolute') . "]Още »[/link]";

            $row->body = $mvc->getVerbal($rec, 'body');
        }

	}
	
	
	/**
	 *  извършва филтриране и подреждане на статиите
	 */
	function on_BeforePrepareListRecs($mvc, $res, $data)
	{
		// Подреждаме статиите по датата им на публикуане в низходящ ред	
		$data->query->orderBy('createdOn', 'DESC');
		
		// Ако метода е 'browse' показваме само активните статии
		if($data->action == 'browse'){
			
			// Показваме само статиите които са активни
			$data->query->where("#state = 'active'");
			
		}
		
		// Ако е зададен id на архив, то показваме статиите от избрания архив
		$arch = Request::get('archive');
		if(isset($arch))
		{
			$data->query->where("#archiveId = {$arch}");
		}
	}
	

    /**
     * След обновяването на коментарите, обновяваме информацията в статията
     */
    function on_AfterUpdateDetail($mvc, $articleId, $Detail)
    {
        if($Detail->className == 'blogm_Comments') {
            $queryC = $Detail->getQuery();
            $queryC->where("#articleId = {$articleId} AND #state = 'active'");
            $rec = $mvc->fetch($articleId);
            $rec->commentsCnt = $queryC->count();
            $mvc->save($rec);
        }
    }
	
	
	/**
	 * Обработка на заглавието
	 */
	function on_AfterPrepareListTitle($mvc, $data)
	{
		// Проверява имали избрана категория
		$category = Request::get('category', 'int');
		
		// Проверяваме имали избрана категория
		if(isset($category)) {
			
			// Ако е избрана се взима заглавието на категорията, което отговаря на посоченото id 
			if($catRec = blogm_Categories::fetch($category)) {
                $title = blogm_Categories::getVerbal($catRec, 'title');
                
                // В заглавието на list  изгледа се поставя името на избраната категория
                $data->title = 'Статии от категория:&nbsp;&nbsp;&nbsp;&nbsp;' . $title;
            }
		}
	}
	
	
	/**
	 * Подготовка на формата за добавяне/редактиране на статия 
	 */
	static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
		$form = $data->form;

        if(!$form->rec->id) {
            $form->setDefault('author', core_Users::getCurrent('nick'));
            $form->setDefault('commentsMode', 'confirmation');
        }
 	}
	
	
	/**
	 *  Филтриране на статиите по ключови думи и категория
	 */
	static function on_AfterPrepareListFilter($mvc, $data)
	{	
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
        $data->listFilter->FNC('category', 'key(mvc=blogm_Categories,select=title,allowEmpty)', 'placeholder=Категория,silent');

        $data->listFilter->showFields = 'search,category';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input(NULL, 'silent');

        if(($cat = $recFilter->category) > 0) {
            $data->query->where("#categories LIKE '%|{$cat}|%'");
        }
     }


    /**
	 *  Екшън за публично преглеждане и коментиране на блог-статия
	 */
	function act_Article()
	{
		// Имаме ли въобще права за Article екшън?			
		$this->requireRightFor('article');

		// Поставяме шаблона за външен изглед
		Mode::set('wrapper', 'cms_tpl_Page');
		
		// Очакваме да има зададено "id" на статията
		$id = Request::get('id', 'int');

        if(!$id) {
            expect($id = Request::get('articleId', 'int'));
        }
		
		// Създаваме празен $data обект
		$data = new stdClass();
		$data->query = $this->getQuery();
		$data->articleId = $id;
        $conf = core_Packs::getConfig('blogm');
        $data->theme = $conf->BLOG_DEFAULT_THEME;

		// Трябва да има $rec за това $id
		expect($data->rec = $this->fetch($id));
		
        // Трябва да имаме права за да видим точно тази статия
		$this->requireRightFor('article', $data->rec);
 		
		// Подготвяме данните за единичния изглед
		$this->prepareArticle($data);
        
        // Обработка на формата за добавяне на коментари
        if($cForm = $data->commentForm) {
        
            // Зареждаме REQUEST данните във формата за коментар
            $rec = $cForm->input();
            
            // Мениджърът на блог-коментарите
            $Comments = cls::get('blogm_Comments');

            // Генерираме събитие в $Comments, след въвеждането на формата
            $Comments->invoke('AfterInputEditForm', array($cForm));
            
            // Дали имаме права за това действие към този запис?
            $Comments->requireRightFor('add', $rec, NULL);
            
            // Ако формата е успешно изпратена - запис, лог, редирект
            if ($cForm->isSubmitted()) {
                
                // Записваме данните
                $id = $Comments->save($rec);
                
                // Правим запис в лога
                $Comments->log('add', $id);
                
                // Редиректваме към предварително установения адрес
                return new Redirect(array('blogm_Articles', 'Article', $data->rec->id), 'Благодарим за вашия коментар;)');
            }
        }
		 // Подготвяме лейаута за статията
        $layout = $this->getArticleLayout($data);
        
		// Рендираме статията във вид за публично разглеждане
		$tpl = $this->renderArticle($data, $layout);
		
		// Записваме, че потребителя е разглеждал този списък
		$this->log('article: ' . ($data->log ? $data->log : tr($data->title)), $id);
		
		
		return $tpl;
	}
	

    /**
     * Моделен метод за подготовка на данните за публично показване на една статия
     */
    function prepareArticle_(&$data)
    {
        $data->rec = $this->fetch($data->articleId);

        $fields = $this->selectFields("");
        
        $fields['-article'] = TRUE;

        $data->row = $this->recToVerbal($data->rec, $fields);

        $this->blogm_Comments->prepareComments($data);
		
        $data->selectedCategories = type_Keylist::toArray($data->rec->categories);
		
       	$this->prepareNavigation($data);

        if($this->haveRightFor('single', $data->rec)) {
            $data->workshop = array('blogm_Articles', 'single', $data->rec->id);
        }
    }
	
	
	/**
     * Рендиране на статия за публичната част на блога
	 */
	function renderArticle_($data, $layout)
	{
		// Поставяме данните от реда
		$layout->placeObject($data->row);
		
		$layout = $this->blogm_Comments->renderComments($data, $layout);

		$this->renderNavigation($data, $layout);
        
        
        // Добавяме стиловете от темата
        $layout->push($data->theme . '/styles.css', 'CSS');
        
		
		return $layout;
	}


    /**
     * Връща лейаута на статия за публично разглеждане
     * Включва коментарите за статията и форма за добавяне на нов
     */
    function getArticleLayout($data)
    {
        return new ET(getFileContent($data->theme . '/Article.shtml'));
    }


    /**
     * Добавяме бутон за преглед на статията в публичната част на сайта
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->haveRightFor('article', $data->rec)) {
            $data->toolbar->addBtn('Преглед', array(
                    $this,
                    'Article',
                    $data->rec->id,
                )
             );
        }
    }
	

	/**
	 *  Показваме списък със статии и навигация по категории
	 */
	function act_Browse()
    {
		// Поставяме шаблона за външен изглед
		Mode::set('wrapper', 'cms_tpl_Page');
 		
        // Евентуално може да има категория
        $category = Request::get('category');

        if($category) {
            expect($catRec = blogm_Categories::fetch($category));
        }
        
        $q = Request::get('q');
		
        $page = Request::get('page');
		if(!isset($page)) {
			$page = 0;
		}
        // Създаваме празен $data обект
		$data = new stdClass();
		$data->query = $this->getQuery();
		$data->category = $category;
		// По какво заглавие търсим
		$data->q = $q;
		// На коя страница сме
		$data->page = $page;
		
		// Ограничаваме показаните статии спрямо спрямо константа и номера на страницата
		$conf = core_Packs::getConfig('blogm');
        $data->theme = $conf->BLOG_DEFAULT_THEME;
        $data->query->limit($conf->BLOG_ARTICLES_LIMIT);
        $data->query->startFrom($data->page * $conf->BLOG_ARTICLES_LIMIT);
        
        // Подготвяме данните необходими за списъка със стаии
        $this->prepareBrowse($data);

        // Рендираме списъка
        $tpl = $this->renderBrowse($data);
        
        // Добавяме стиловете от темата
        $tpl->push($data->theme . '/styles.css', 'CSS');

		// Записваме, че потребителя е разглеждал този списък
		$this->log('List: ' . ($data->log ? $data->log : tr($data->title)));
		
		
		return $tpl;
	}

	
	/**
	 * Подготвяме навигационното меню
	 */
	function prepareNavigation(&$data){
		
		$this->prepareSearch($data);
        
        blogm_Archives::prepareArchives($data);
        
        blogm_Categories::prepareCategories($data);
	}
	
	
    /**
     * Подготвяме данните за показването на списъка с блог-статии
     */
    function prepareBrowse($data)
    {   
        if($data->category) {
            $data->query->where("#categories LIKE '%|{$data->category}|%'");
            $data->selectedCategories[$data->category] = TRUE;
        }
        
        if($data->archive) {
        	 $data->query->where("#archiveId = {$data->archive}");
        	 $data->selectedArchive[$data->archive] = TRUE;
        }
        
        if($data->q) {
        	plg_Search::applySearch($data->q, $data->query);
        }
       
        $data->query->orderBy('createdOn', 'DESC');
        
        // Показваме само публикуваните статии
        $data->query->where("#state = 'active'");
		
        
        $fields = $this->selectFields("");
        $fields['-browse'] = TRUE;
        
        while($rec = $data->query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $data->rows[$rec->id] = $this->recToVerbal($rec, $fields);
            $data->rows[$rec->id]->title = ht::createLink(
                $data->rows[$rec->id]->title,
                array('blogm_Articles', 'Article', $rec->id)
            );
        }

        if($this->haveRightFor('list')) {
            $data->workshop = array('blogm_Articles', 'list');
        }

        blogm_Categories::prepareCategories($data);
    }
	
	
	/**
	 * Нов екшън, който рендира листовия списък на статиите за външен достъп, Той връща 
	 * нов темплейт, който представя таблицата в подходящия нов дизайн, създаден е по
	 * аналогия на renderList  с заменени методи които да рендират в новия изглед
	 */
	function renderBrowse_($data)
    {
		$layout = new ET(getFileContent($data->theme . '/Browse.shtml'));
        
        if(count($data->rows)) {
            foreach($data->rows as $row) {
                $rowTpl = $layout->getBlock('ROW');
                $rowTpl->placeObject($row);
                $rowTpl->append2master();
            }
            
            $conf = core_Packs::getConfig('blogm');
            
        	// Ако страницата е различна от първата
            if($data->page != 0) {
            	
            	// Намаляваме страницата с 1 и добавяме линк за връщане на предната страница
            	$forward = $data->page - 1;
            	$layout->append(ht::createLink("По-нови статии", array('blogm_Articles', 'browse', 'category' => $data->category, 'page' => $forward)), 'newerArticles');
            }
            
            // Ако броя на статиите е по-голям или равен на ограничението, ние добавяме
            // бутона за преминаване на следващата страница
            if(count($data->rows) >= $conf->BLOG_ARTICLES_LIMIT) {
            	
            	// Изчисляваме коя  е следващата страница и поставяме линк
            	$back = $data->page + 1;
            	$layout->append(ht::createLink("По-стари статии", array('blogm_Articles', 'browse', 'category' => $data->category, 'page' => $back)), 'olderArticles');
             }
        } else {
            $rowTpl = $layout->getBlock('ROW');
            $rowTpl->replace('<h2>Няма статии</h2>');
            $rowTpl->append2master();
        }
        
		// Ако е посочено заглавие по-което се търси
        if(isset($data->q)) {
			$layout->replace('Резултати за &nbsp;&nbsp;"<b>' . $data->q . '</b>"<br><br>', 'results');
		}
        // Рендираме навигацията
        $this->renderNavigation($data, $layout);
		
        
		return $layout;
	}
	
	
	/**
	 * Екшън, който зарежда статиите, които принадлежат към избрания архив
	 */
	function act_Archive(){
		
		// Поставяме шаблона за външен изглед
		Mode::set('wrapper', 'cms_tpl_Page');
 		$aId = Request::get('aId');
		
 		// Очакваме да има такъв архив
        if($aId) {
            expect($archRec = blogm_Archives::fetch($aId));
        }
       

		// Създаваме празен $data обект
		$data = new stdClass();
		$data->query = $this->getQuery();
		$data->archive = $aId;
		$conf = core_Packs::getConfig('blogm');
        $data->theme = $conf->BLOG_DEFAULT_THEME;
         
		 // Подготвяме данните необходими за списъка със статии
        $this->prepareBrowse($data);
        
        // Рендираме архива
        $tpl = $this->renderArchive($data);
        
        // Добавяме стиловете от темата
        $tpl->push($data->theme . '/styles.css', 'CSS');

		// Записваме, че потребителя е разглеждал този списък
		$this->log('Archive: ' . ($data->log ? $data->log : tr($data->title)));
		
		
		return $tpl;
	
	}
 
	/**
	 * Рендиране на Архива
	 */ 
	function renderArchive_(&$data){
		$layout = new ET(getFileContent($data->theme . '/Archive.shtml'));
		$layout->replace(blogm_Archives::fetch($data->archive)->title, 'range');
		$layout->replace(count($data->rows), 'count');
		
		if(count($data->rows)) {
	            foreach($data->rows as $row) {
	                $rowTpl = $layout->getBlock('ROW');
	                $rowTpl->placeObject($row);
	                $rowTpl->append2master();
	            }
		} else {
			$rowTpl = $layout->getBlock('ROW');
            $rowTpl->replace('<h2>Няма статии</h2>');
            $rowTpl->append2master(); 
		}   
	    // Рендираме навигацията
		$this->renderNavigation($data, $layout);
		
	
		return $layout;
	}
	
	
	/**
     * Подготвяме формата за търсене
     */
    function prepareSearch_(&$data){
		$form = cls::get('core_Form');
		$form->FNC('q','varchar(100)', 'input,width=95%,placeholder=търси ....');
		$form->setAction(array('blogm_Articles', Request::get('Act'), 'search'));
		// Нов Събмит бутон с иконка 
		$form->toolbar->addSbBtn(' ', '', 'input=none,id=sBtn ');
		$data->searchForm = $form;
	}
	
	
	/**
	 * Рендираме формата за търсене
	 */
	function renderSearch_(&$data){
		$data->searchForm->layout = new ET(getFileContent($data->theme . '/SearchForm.shtml'));
		$data->searchForm->fieldsLayout = new ET(getFileContent($data->theme . '/SearchFormFields.shtml'));
		
		
		return $data->searchForm->renderHtml();
	}
	
	
	/**
	 * Функция което рендира менюто с категориите, формата за търсене, и менюто с архивите
	 */
	function renderNavigation($data, &$layout) {
		
		// Рендираме категориите
 		$layout->append(blogm_Categories::renderCategories($data), 'NAVIGATION');
 		if($data->singleUrl) {
            $layout->append(ht::createBtn('Работилница', $data->singleUrl), 'NAVIGATION');
        }
        // Рендираме формата за търсене
		$layout->append($this->renderSearch($data), 'NAVIGATION');
		
		// Рендираме Списъка с архивите
		$layout->append(blogm_Archives::renderArchives($data), 'NAVIGATION');
	}
	
	
	/**
     * Какви роли са необходими за посоченото действие?
     */
	function on_AfterGetRequiredRoles($mvc, &$roles, $act, $rec = NULL, $user = NULL)
    {
        if($act == 'article' && isset($rec)) {
            if($rec->state != 'active') {
                // Само тези, които могат да създават и редактират статии, 
                // могат да виждат статиите, които не са активни (публични)
                $roles = $mvc->canWrite;
            }
        }
    }
	
}

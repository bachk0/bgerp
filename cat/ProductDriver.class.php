<?php

/**
 * Базов драйвер за драйвер на артикул
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Базов драйвер за драйвер на артикул
 */
abstract class cat_ProductDriver extends core_BaseClass
{
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectDriver = 'ceo, cat, sales';
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'cat_ProductDriverIntf';
	
	
	/**
	 * Записа на мениджъра, в който е вграден драйвера
	 */
	public $driverRec;
	
	
	/**
	 * Мениджъра в който в вграден драйвера
	 */
	protected $Embedder;

	
	/**
	 * Мета данни по подразбиране
	 * 
	 * @param strint $defaultMetaData
	 */
	protected $defaultMetaData;
	
	
	/**
	 * Параметри
	 *
	 * @param array $driverParams
	 */
	protected $driverParams;
	
	
	/**
	 * Записа на ембедера
	 */
	public $EmbedderRec;
	
	
	/**
	 * Добавя полетата на драйвера към Fieldset
	 *
	 * @param core_Fieldset $fieldset
	 */
	public function addFields(core_Fieldset &$fieldset)
	{
		
	}
	
	
	/**
	 * Кой може да избере драйвера
	 */
	public function canSelectDriver($userId = NULL)
	{
		return core_Users::haveRole($this->canSelectDriver, $userId);
	}
	
	
	/**
	 * Преди показване на форма за добавяне/промяна.
	 *
	 * @param core_Manager $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareEditForm($Driver, &$data)
	{
		$form = &$data->form;
		
		// Намираме полетата на формата
		$fields = $form->selectFields();
		
		if(count($Driver->driverParams)){
			
			// Ако в параметрите има стойност за поле, което е във формата задаваме му стойността
			foreach ($fields as $name => $fld){
				if(isset($this->driverParams[$name])){
					$form->setDefault($name, $Driver->driverParams[$name]);
				}
			}
		}
		
		// Ако има полета
		if(count($fields)){
			
			// За всички полета
			foreach ($fields as $name => $fld){
					
				// Ако има атрибут display
				$display = $form->getFieldParam($name, 'display');
					
				// Ако е 'hidden' и има зададена стойност, правим полето скрито
				if($display === 'hidden'){
					if(!is_null($form->rec->$name)){
						$form->setField($name, 'input=hidden');
					}
				} elseif($display === 'readOnly'){
			
					// Ако е 'readOnly' и има зададена стойност, правим го 'само за четене'
					if(!is_null($form->rec->$name)){
						$form->setReadOnly($name);
					}
				}
			}
		}
	}
	
	
	/**
	 * Връща счетоводните свойства на обекта
	 */
	public function getFeatures($productId)
	{
		return array();
	}

	
	/**
	 * Кои опаковки поддържа продукта
	 *
	 * @param array $metas - кои са дефолтните мета данни от ембедъра
	 * @return array $metas - кои са дефолтните мета данни
	 */
	public function getDefaultMetas($metas)
	{
		// Взимаме дефолтните мета данни от ембедъра
		$metas = arr::make($metas, TRUE);
	
		// Ако за драйвера има дефолтни мета данни, добавяме ги към тези от ембедъра
		if(!empty($this->defaultMetaData)){
			$metas = $metas + arr::make($this->defaultMetaData, TRUE);
		}
	
		return $metas;
	}
	
	
	/**
	 * Връща параметрите на артикула
	 * @param mixed $productId - ид или запис на артикул
	 *
	 * @return array $res - параметрите на артикула
	 * 					['weight']          -  Тегло
	 * 					['width']           -  Широчина
	 * 					['volume']          -  Обем
	 * 					['thickness']       -  Дебелина
	 * 					['length']          -  Дължина
	 * 					['height']          -  Височина
	 * 					['tolerance']       -  Толеранс
	 * 					['transportWeight'] -  Транспортно тегло
	 * 					['transportVolume'] -  Транспортен обем
	 * 					['term']            -  Срок
	 */
	public function getParams($productId)
	{
		$res = array();
		foreach (array('weight', 'width', 'volume', 'thickness', 'length', 'height', 'tolerance', 'transportWeight', 'transportVolume', 'term') as $p){
			$res[$p] = NULL;
		}
		
		return $res;
	}
	

	/**
	 * Подготвя данните за показване на описанието на драйвера
	 *
	 * @param enum(public,internal) $documentType - публичен или външен е документа за който ще се кешира изгледа
	 */
	public function prepareProductDescription($documentType = 'public')
	{
		return (object)array();
	}
	
	
	/**
	 * Променя ключовите думи от мениджъра
	 */
	public function alterSearchKeywords(&$searchKeywords)
	{
		
	}
	
	
	
	
	
	
	
	
	/**
	 * Задава параметрите на обекта
	 *
	 * @param mixed $innerForm
	 */
	public function setDriverParams($params)
	{
		$params = arr::make($params, TRUE);
		if(count($params)){
			$this->driverParams = arr::make($params, TRUE);
		}
	}
	
	
	/**
	 * Връща параметрите на драйвера
	 */
	public function getDriverParams()
	{
		return $this->driverParams;
	}
	
	
	/**
	 * Кои документи са използвани в полетата на драйвера
	 */
	public function getUsedDocs()
	{
		return FALSE;
	}
	
	
	/**
	 * Връща основната мярка, специфична за технолога
	 */
	public function getDriverUom($params = array())
	{
		if(empty($params['measureId'])){
			
			return cat_UoM::fetchBySysId('s')->id;
		}
		
		return $params['measureId'];
	}
	
	
	/**
	 * Изображението на артикула
	 */
	public function getProductImage()
	{
		return NULL;
	}
	
	
	/**
	 * Рендира данните за показване на артикула
	 */
	public function renderProductDescription($data)
	{
		return new core_ET("");
	}
	
	
	/**
	 * Как да се казва дефолт папката където ще отиват заданията за артикулите с този драйвер
	 */
	public function getJobFolderName()
	{
		$title = core_Classes::fetchField($this->getClassId(), 'title');
		
		return "Задания за " . mb_strtolower($title);
	}
	
	
	/**
	 * Подготвя данните необходими за показването на вградения обект
	 *
	 * @param core_Form $innerForm
	 * @param stdClass $innerState
	 */
	public function prepareEmbeddedData()
	{
		$data = new stdClass();
		$row = new stdClass();
	
		$form = new core_Form();
		$this->addEmbeddedFields($form);
		$this->prepareEmbeddedForm($form);
		$fields = $form->selectFields();
		foreach($fields as $name => $fld){
			$captionArr = explode('->', $fld->caption);
			$caption = (count($captionArr) == 2) ? $captionArr[1] : $fld->caption;
				
			$row->{$caption} = $form->getFieldType($name)->toVerbal($this->innerForm->$name);
		}
	
		$data->row = $row;
	
		return $data;
	}
	
	
	/**
	 * Връща информация за какви дефолт задачи могат да се задават към заданието за производство
	 * 
	 * @return array $drivers - масив с информация за драйверите, с ключ името на масива
	 * 				    -> title        - дефолт име на задачата
	 * 					-> driverClass  - драйвър на задача
	 * 					-> priority     - приоритет (low=Нисък, normal=Нормален, high=Висок, critical)
	 */
	public function getDefaultJobTasks()
	{bp();
		return array();
	}
}
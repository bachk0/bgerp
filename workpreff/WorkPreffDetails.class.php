<?php



/**
 * Клас 'workpreff_WorkPreffDetails'
 *
 * Детайли на мениджър за създаване на Форма за CV 
 *
 * @category  bgerp
 * @package   workpreff
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */

class workpreff_WorkPreffDetails extends core_Detail
{
	
	/**
	 * Заглавие
	 *
	 * @var string
	 */
	public $title = 'Детайли на форма за CV';
	
	
	/**
	 * Заглавие в единствено число
	 *
	 * @var string
	 */
	public $singleTitle = 'Избор';
	
	
	/**
	 * Плъгини за зареждане
	 *
	 * var string|array
	 */
	public $loadList = 'plg_RowTools2, plg_Created, sales_Wrapper, plg_RowNumbering, plg_SaveAndNew, plg_PrevAndNext';
			
            //            plg_AlignDecimals2, plg_Sorting, deals_plg_ImportDealDetailProduct, doc_plg_HidePrices, LastPricePolicy=sales_SalesLastPricePolicy,
			  //          cat_plg_CreateProductFromDocument,doc_plg_HideMeasureAndQuantityColumns,cat_plg_ShowCodes';
	
	

	/**
	 * Име на поле от модела, външен ключ към мастър записа
	 */
	public $masterKey = 'choiceId';






	/**
	 * Описание на модела (таблицата)
	 */
	public function description()
	{
		$this->FLD('choiceId', 'key(mvc=workpreff_WorkPreff)', 'column=none,notNull,silent,hidden,mandatory');
	
		
	}























}



<?php
class cat_Prices extends core_Manager
{
	var $title = 'Цени';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_RowTools, plg_SaveAndNew,
                     cat_Wrapper, plg_Sorting, plg_Printing';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id,productId,packagingId,validFrom,cost,price,discount';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,cat';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,cat,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,cat,broker';
    
    var $canList = 'admin,cat,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,cat';
	
    
    function description()
	{
		$this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Продукт');
		
		// Вид опаковка. Ако е пропуснат, записа се отнася за основната мярка
		$this->FLD('packagingId', 'key(mvc=cat_Packagings,select=name, allowEmpty)', 'caption=Опаковка');
		
		// Валидност от дата
		$this->FLD('validFrom', 'datetime', 'caption=В сила от');

		// Себестойност и продажна цена
		$this->FLD('cost', 'double', 'caption=Цена->Себестойност');
		$this->FLD('price', 'double', 'caption=Цена->Продажна');
		
		// отстъпка от крайната цена до себестойността
		$this->FLD('discount', 'percent', 'caption=Цена->Отстъпка');
	}
}
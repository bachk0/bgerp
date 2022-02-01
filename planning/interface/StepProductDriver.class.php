<?php


/**
 * Драйвър за артикул - производствен етап
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2022 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Етап в производството
 */
class planning_interface_StepProductDriver extends cat_GeneralProductDriver
{
    /**
     * За конвертиране на съществуващи MySQL таблици от предишни версии
     */
    public $oldClassName = 'planning_interface_StageDriver';


    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, planningMaster';
    
    
    /**
     * Мета данни по подразбиране
     *
     * @param string $defaultMetaData
     */
    protected $defaultMetaData = 'canManifacture,canConvert,canStore';
    
    
    /**
     * Клас екстендър, който да се закача
     *
     * @param string
     */
    public $extenderClass = 'planning_Steps';
    
    
    /**
     * Икона на артикулите
     */
    protected $icon = 'img/16/paste_plain.png';
    
    
    /**
     * Подготвяне на вербалните стойности
     *
     * @param cal_Progresses $Driver
     * @param doc_Comments   $mvc
     * @param stdClass       $row
     * @param stdClass       $rec
     */
    protected function on_AfterRecToVerbal(cat_ProductDriver $Driver, $mvc, $row, $rec)
    {
       // unset($row->editMetaBtn);
    }
    
    
    /**
     * Връща дефолтната дефиниция за шаблон на партидна дефиниция
     *
     * @param mixed $id - ид или запис на артикул
     *
     * @return int - ид към batch_Templates
     */
    public function getDefaultBatchTemplate($id)
    {
        $templateId = batch_Templates::fetchField("#createdBy = '-1' AND #state = 'active' AND #driverClass =" . batch_definitions_Job::getClassId());
    
        return !empty($templateId) ? $templateId : null;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     *
     * @param mixed                                                                              $productId - ид на артикул
     * @param int                                                                                $quantity  - к-во
     * @param float                                                                              $minDelta  - минималната отстъпка
     * @param float                                                                              $maxDelta  - максималната надценка
     * @param datetime                                                                           $datetime  - дата
     * @param float                                                                              $rate      - валутен курс
     * @param string $chargeVat - начин на начисляване на ддс
     *
     * @return stdClass|float|NULL $price  - обект с цена и отстъпка, или само цена, или NULL ако няма
     */
    public function getPrice($productId, $quantity, $minDelta, $maxDelta, $datetime = null, $rate = 1, $chargeVat = 'no')
    {
        return 0;
    }
    
    
    /**
     * Подготвя групите, в които да бъде вкаран продукта
     */
    public static function on_BeforeSave($Driver, embed_Manager &$Embedder, &$id, &$rec, $fields = null)
    {
        if(empty($rec->id) && $Embedder instanceof cat_Products){
            $groupId = cat_Groups::fetchField("#sysId = 'prefabrications'");
            $rec->groupsInput = keylist::addKey($rec->groupsInput, $groupId);
            $rec->groups = keylist::fromArray($Embedder->expandInput(type_Keylist::toArray($rec->groupsInput)));
        }
    }


    /**
     * Пренасочва URL за връщане след запис към сингъл изгледа
     */
    public static function on_AfterPrepareRetUrl($Driver, embed_Manager &$Embedder, $res, $data)
    {
        // Ако се иска директно контиране редирект към екшъна за контиране
        if (isset($data->form) && $data->form->isSubmitted() && $data->form->rec->id) {
            $retUrl = getRetUrl();

            // Ако се създава от рецепта: да редиректне към нея с вече готовото ид
            if($retUrl['Ctr'] == 'cat_BomDetails' && $retUrl['type'] == 'stage'){
                if(cat_Products::haveDriver($data->form->rec->id, 'planning_interface_StepProductDriver')){
                    if($Driver = cat_Products::getDriver($data->form->rec->id)){
                        if ($Driver->canSelectDriver()) {
                            $retUrl['resourceId'] = $data->form->rec->id;
                            $data->retUrl = $retUrl;
                        }
                    }
                }
            }
        }
    }


    /**
     * Връща информация за данните от производствения етап
     *
     * @param int $productId
     * @return array
     *          int|null   ['centerId']    - ид на център на дейност
     *          int|null   ['storeIn']     - ид на склад за засклаждане (ако е складируем)
     *          int|null   ['storeInput']  - ид на склад за влагане (ако е складируем)
     *          array|null ['fixedAssets'] - масив от ид-та на оборудвания (@see planning_AssetResources)
     *          array|null ['employees']   - масив от ид-та на оператори (@see planning_Hr)
     *          int|null   ['norm']        - норма за производство
     */
    public function getProductionStepData($productId)
    {
        $rec = cat_Products::fetch($productId);
        $res = array('centerId' => $rec->planning_Steps_centerId, 'storeIn' => $rec->planning_Steps_storeIn, 'storeInput' => $rec->planning_Steps_storeInput, 'norm' => $rec->planning_Steps_norm);
        $res['fixedAssets'] = !empty($rec->planning_Steps_fixedAssets) ? keylist::toArray($rec->planning_Steps_fixedAssets) : null;
        $res['employees'] = !empty($rec->planning_Steps_employees) ? keylist::toArray($rec->planning_Steps_employees) : null;

        return $res;
    }
}
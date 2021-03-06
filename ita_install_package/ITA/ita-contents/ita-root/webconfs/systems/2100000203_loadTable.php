<?php
//   Copyright 2019 NEC Corporation
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//   you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.
//
//////////////////////////////////////////////////////////////////////
//
//  【処理概要】
//    ・WebDBCore機能を用いたWebページの中核設定を行う。
//
//////////////////////////////////////////////////////////////////////
    
$tmpFx = function (&$aryVariant=array(),&$arySetting=array()){
    global $g;

    $arrayWebSetting = array();

    $arrayWebSetting['page_info'] = $g['objMTS']->getSomeMessage("ITAWDCH-MNU-1020001");

    // 項番
    $table = new TableControlAgent('A_PERMISSIONS_LIST','PERMISSIONS_ID', $g['objMTS']->getSomeMessage("ITAWDCH-MNU-1020101"), 'A_PERMISSIONS_LIST_JNL');
    $table->setDBMainTableLabel($g['objMTS']->getSomeMessage("ITAWDCH-MNU-1020002"));
    $table->getFormatter("excel")->setGeneValue("sheetNameForEditByFile",$g['objMTS']->getSomeMessage("ITAWDCH-MNU-1020003"));

    $table->setGeneObject('AutoSearchStart',true);  //('',true,false)
    
    $table->setJsEventNamePrefix(true);
    $table->setGeneObject("webSetting", $arrayWebSetting);

    $tmpAryObjColumn = $table->getColumns();
    $tmpAryObjColumn['PERMISSIONS_ID']->setSequenceID('SEQ_A_PERMISSIONS_LIST');

    // IPアドレス値
    $c = new TextColumn('IP_ADDRESS',$g['objMTS']->getSomeMessage("ITAWDCH-MNU-1020201"));
    $c->setRequired(true);
    $c->setUnique(true);
    $c->setDescription($g['objMTS']->getSomeMessage("ITAWDCH-MNU-1020202"));
    $strPattern = "/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/";
    $objVldt = new TextValidator(7, 15, false, $strPattern, "xxx.xxx.xxx.xxx");
    $objVldt->setRegexp("/^[^,\"\r\n]*$/s","DTiS_filterDefault");
    $objVldt->setMinLength(0,"DTiS_filterDefault");
    $c->setValidator($objVldt);
    $table->addColumn($c);

    // メモ
    $c = new TextColumn('IP_INFO',$g['objMTS']->getSomeMessage("ITAWDCH-MNU-1020301"));
    $c->setDescription($g['objMTS']->getSomeMessage("ITAWDCH-MNU-1020302"));
    $c->setValidator(new SingleTextValidator(0, 256, false));
    $table->addColumn($c);

    $table->fixColumn();    
    
    return $table;
};
loadTableFunctionAdd($tmpFx,__FILE__);
unset($tmpFx);
?>

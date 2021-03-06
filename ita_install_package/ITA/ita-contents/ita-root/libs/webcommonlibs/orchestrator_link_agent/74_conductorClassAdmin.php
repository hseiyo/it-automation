
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

function getPatternListWithOrchestratorInfo($fxVarsStrFilterData="",$fxVarsResultType=0){
    global $g;
    $boolRet = false;
    $intErrorType = null;
    $aryErrMsgBody = array();
    $strErrMsg = "";
    $aryListSource = array();
    
    $intControlDebugLevel01=250;
    
    $objMTS = $g['objMTS'];
    $objDBCA = $g['objDBCA'];
    
    $strFxName = '([FUNCTION]'.__FUNCTION__.')';
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-3",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    
    $strSysErrMsgBody = "";
    
    try{
        require_once($g['root_dir_path']."/libs/commonlibs/common_ola_classes.php");
        $objOLA = new OrchestratorLinkAgent($objMTS,$objDBCA);
        
        $aryRet = $objOLA->getLiveOrchestratorFromMaster();
        if( $aryRet[1] !== null ){
            // エラーフラグをON
            // 例外処理へ
            $intErrorType = $aryRet[1];
            $strErrStepIdInFx="00000100";
            
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        
        $aryOrcListRow = $aryRet[0];
        
        $boolBinaryDistinctOnDTiS = false;
        
        //オーケストレータ情報の収集----
        
        //----存在するオーケスト—タ分回る
        foreach($aryOrcListRow as $arySingleOrcInfo){
            $varOrcId = $arySingleOrcInfo['ITA_EXT_STM_ID'];
            $varOrcRPath = $arySingleOrcInfo['ITA_EXT_LINK_LIB_PATH'];
            
            $objOLA->addFuncionsPerOrchestrator($varOrcId,$varOrcRPath);
            
            $aryRet = $objOLA->getLivePatternList($varOrcId,$fxVarsStrFilterData,$boolBinaryDistinctOnDTiS);
            if( $aryRet[1] !== null ){
                // エラーフラグをON
                // 例外処理へ
                $intErrorType = $aryRet[1];
                $strErrStepIdInFx="00000200";
                //
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            //
            $aryRow = $aryRet[0];
            
            //----オーケストレータカラーを取得
            $aryRet = $objOLA->getThemeColorName($varOrcId);
            if( $aryRet[1] !== null ){
                // エラーフラグをON
                // 例外処理へ
                $intErrorType = $aryRet[1];
                $strErrStepIdInFx="00000300";
                
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            $strThemeColor = $aryRet[0];
            //オーケストレータカラーを取得----
            
            if( $fxVarsResultType === 1 ){
                foreach($aryRow as $arySingleRow){
                    $aryListSource[] = $varOrcId;
                    $aryListSource[] = $arySingleRow['PATTERN_ID'];
                    $aryListSource[] = $arySingleRow['PATTERN_NAME'];
                    $aryListSource[] = $strThemeColor;
                }
            }
            else{
                foreach($aryRow as $arySingleRow){
                    $tmpRow = array();
                    $intPatternId = $arySingleRow['PATTERN_ID'];
                    //
                    $tmpRow['PATTERN_ID']      = $intPatternId;
                    $tmpRow['ORCHESTRATOR_ID'] = $varOrcId;
                    $tmpRow['PATTERN_NAME']    = $arySingleRow['PATTERN_NAME'];
                    $tmpRow['ThemeColor']      = $strThemeColor;
                    //
                    $aryListSource[$intPatternId] = $tmpRow;
                }
            }
        }
        //存在するオーケスト—タ分回る----
    }
    catch (Exception $e){
        // エラーフラグをON
        if( $intErrorType === null ) $intErrorType = 500;
        $tmpErrMsgBody = $e->getMessage();
        if( 500 <= $intErrorType ) $strSysErrMsgBody = $objMTS->getSomeMessage("ITAWDCH-ERR-4011",array($strFxName,$tmpErrMsgBody));
        if( 0 < strlen($strSysErrMsgBody) ) web_log($strSysErrMsgBody);
    }
    $retArray = array($boolRet,$intErrorType,$aryErrMsgBody,$strErrMsg,$aryListSource);
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-4",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    return $retArray;
}

//----Conductorのパラメータの整形
function nodeDateDecodeForEdit($fxVarsStrSortedData){
    global $g;
    $aryMovement = array();
    $intErrorType = null;
    $aryErrMsgBody = array();
    $strErrMsg = "";
    
    $intControlDebugLevel01=250;
    
    $objMTS = $g['objMTS'];
    
    $strFxName = '([FUNCTION]'.__FUNCTION__.')';
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-3",array(__FILE__,$strFxName)),$intControlDebugLevel01);

    $strSysErrMsgBody = "";

    $intLengthArySettingForParse = count($fxVarsStrSortedData);

    $aryMovement = array();
    //node分繰り返し
    $aryNode = array();
    $arrpatternDel = array('/__proto__/');
    $arrpatternPrm = array('/node/','/id/','/type/','/note/','/condition/','/case/','/x/','/y/','/w/','/h/','/edge/','/targetNode/','/PATTERN_ID/','/ORCHESTRATOR_ID/','/OPERATION_NO_IDBH/','/SYMPHONY_CALL_CLASS_NO/','/SKIP_FLAG/','/CONDUCTOR_CALL_CLASS_NO/','/CALL_CONDUCTOR_ID/' );

    foreach( $fxVarsStrSortedData as $nodename => $nodeinfo ){
        //　nodeの処理開始
        if( strpos($nodename,'node-') !== false  ){
            foreach ($nodeinfo as $key => $value) {
                #nodeパラメータ整形
                $ASD = preg_replace( $arrpatternPrm, "" , $key );
                if( $ASD == "" ){
                    if( is_array($value) ){
                        foreach ($value as $optionkey => $optionval) {
                            $aryNode[$nodename][$optionkey]=$optionval;
                        }
                    }else{
                        $aryNode[$nodename][$key]=$value;
                    }
                    #terminalパラメータ
                }elseif( strpos($key,'terminal') !== false  ){
                    foreach ($value as $terminalname => $terminalarr) {
                        if( is_array($terminalarr) ){
                            #terminalパラメータ整形
                            foreach ($terminalarr as $terminalkey => $terminalinfo) {
                                $ZXC = preg_replace( $arrpatternDel, "" , $terminalkey);
                                if( is_array($terminalinfo) && isset($terminalarr['condition'])){
                                    foreach ($terminalinfo as $arrterminalval)$aryNode[$nodename][$key][$terminalname][$terminalkey][] = $arrterminalval;
                                 }elseif( $ZXC != ""  ){
                                    if( !is_array($terminalinfo) && strlen($terminalkey) >= 1){
                                        $aryNode[$nodename][$key][$terminalname][$terminalkey] = $terminalinfo ;
                                    }
                                }
                            }
                        }
                    }            
                }
            }                
        }
    }

    return $aryNode;

}
//Conductorのパラメータの整形----

//----ある１のConductorの定義を新規登録（追加）する
function conductorClassRegisterExecute($fxVarsIntConductorClassId ,$fxVarsAryReceptData, $fxVarsStrSortedData, $fxVarsStrLT4UBody){


    // グローバル変数宣言
    global $g;
    $arrayResult = array();
    $strResultCode = "";
    $strDetailCode = "000";
    $intConductorClassId = '';
    $strExpectedErrMsgBodyForUI = "";
    
    $intControlDebugLevel01=250;
    
    $objMTS = $g['objMTS'];
    $objDBCA = $g['objDBCA'];
    
    $intErrorType = null;
    $intDetailType = null;
    $aryErrMsgBody = array();
   
    $strFxName = '([FUNCTION]'.__FUNCTION__.')';
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-3",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    
    $aryConfigForSymClassIUD = array(
        "JOURNAL_SEQ_NO"=>"",
        "JOURNAL_ACTION_CLASS"=>"",
        "JOURNAL_REG_DATETIME"=>"",
        "CONDUCTOR_CLASS_NO"=>"",
        "CONDUCTOR_NAME"=>"",
        "DESCRIPTION"=>"",
        "NOTE"=>"",
        "DISUSE_FLAG"=>"",
        "LAST_UPDATE_TIMESTAMP"=>"",
        "LAST_UPDATE_USER"=>""
    );
    
    $arySymClassValueTmpl = array(
        "JOURNAL_SEQ_NO"=>"",
        "JOURNAL_ACTION_CLASS"=>"",
        "JOURNAL_REG_DATETIME"=>"",
        "CONDUCTOR_CLASS_NO"=>"",
        "CONDUCTOR_NAME"=>"",
        "DESCRIPTION"=>"",
        "NOTE"=>"",
        "DISUSE_FLAG"=>"",
        "LAST_UPDATE_TIMESTAMP"=>"",
        "LAST_UPDATE_USER"=>""
    );

    $arrayConfigForNodeClassIUD = array(
        "JOURNAL_SEQ_NO"=>"",
        "JOURNAL_REG_DATETIME"=>"",
        "JOURNAL_ACTION_CLASS"=>"",
        "NODE_CLASS_NO"=>"",
        "NODE_NAME"=>"",
        "NODE_TYPE_ID"=>"",
        "ORCHESTRATOR_ID"=>"",
        "PATTERN_ID"=>"",
        "CONDUCTOR_CALL_CLASS_NO"=>"",
        "DESCRIPTION"=>"",
        "CONDUCTOR_CLASS_NO"=>"",
        "OPERATION_NO_IDBH"=>"",
        "SKIP_FLAG"=>"",
        "NEXT_PENDING_FLAG"=>"",
        "POINT_X"=>"",
        "POINT_Y"=>"",
        "POINT_W"=>"",
        "POINT_H"=>"",
        "DISP_SEQ"=>"",
        "NOTE"=>"",
        "DISUSE_FLAG"=>"",
        "LAST_UPDATE_TIMESTAMP"=>"",
        "LAST_UPDATE_USER"=>""
    ); 
    
    $aryNodeClassValueTmpl = array(
        "JOURNAL_SEQ_NO"=>"",
        "JOURNAL_REG_DATETIME"=>"",
        "JOURNAL_ACTION_CLASS"=>"",
        "NODE_CLASS_NO"=>"",
        "NODE_NAME"=>"",
        "NODE_TYPE_ID"=>"",
        "ORCHESTRATOR_ID"=>"",
        "PATTERN_ID"=>"",
        "CONDUCTOR_CALL_CLASS_NO"=>"",
        "DESCRIPTION"=>"",
        "CONDUCTOR_CLASS_NO"=>"",
        "OPERATION_NO_IDBH"=>"",
        "SKIP_FLAG"=>"",
        "NEXT_PENDING_FLAG"=>"",
        "POINT_X"=>"",
        "POINT_Y"=>"",
        "POINT_W"=>"",
        "POINT_H"=>"",
        "DISP_SEQ"=>"",
        "NOTE"=>"",
        "DISUSE_FLAG"=>"",
        "LAST_UPDATE_TIMESTAMP"=>"",
        "LAST_UPDATE_USER"=>""
    );
    

    $arrayConfigForTermClassIUD = array(
        "JOURNAL_SEQ_NO"=>"",
        "JOURNAL_REG_DATETIME"=>"",
        "JOURNAL_ACTION_CLASS"=>"",
        "TERMINAL_CLASS_NO"=>"",
        "TERMINAL_CLASS_NAME"=>"",
        "TERMINAL_TYPE_ID"=>"",
        "NODE_CLASS_NO"=>"",
        "CONDUCTOR_CLASS_NO"=>"",
        "CONNECTED_NODE_NAME"=>"",
        "LINE_NAME"=>"",
        "TERMINAL_NAME"=>"",
        "CONDITIONAL_ID"=>"",
        "CASE_NO"=>"",
        "DESCRIPTION"=>"",
        "POINT_X"=>"",
        "POINT_Y"=>"",
        "DISP_SEQ"=>"",
        "NOTE"=>"",
        "DISUSE_FLAG"=>"",
        "LAST_UPDATE_TIMESTAMP"=>"",
        "LAST_UPDATE_USER"=>""
    ); 
    
    $aryTermClassValueTmpl = array(
        "JOURNAL_SEQ_NO"=>"",
        "JOURNAL_REG_DATETIME"=>"",
        "JOURNAL_ACTION_CLASS"=>"",
        "TERMINAL_CLASS_NO"=>"",
        "TERMINAL_CLASS_NAME"=>"",
        "TERMINAL_TYPE_ID"=>"",
        "NODE_CLASS_NO"=>"",
        "CONDUCTOR_CLASS_NO"=>"",
        "CONNECTED_NODE_NAME"=>"",
        "LINE_NAME"=>"",
        "TERMINAL_NAME"=>"",
        "CONDITIONAL_ID"=>"",
        "CASE_NO"=>"",
        "DESCRIPTION"=>"",
        "POINT_X"=>"",
        "POINT_Y"=>"",
        "DISP_SEQ"=>"",
        "NOTE"=>"",
        "DISUSE_FLAG"=>"",
        "LAST_UPDATE_TIMESTAMP"=>"",
        "LAST_UPDATE_USER"=>""
    );

    $strSysErrMsgBody = "";
    $boolInTransactionFlag = false;
 
    try{
        require_once($g['root_dir_path']."/libs/commonlibs/common_ola_classes.php");
        $objOLA = new OrchestratorLinkAgent($objMTS,$objDBCA);

        //----バリデーションチェック(入力形式)
        $objIntNumVali = new IntNumValidator(null,null,"",array("NOT_NULL"=>false));
        if( $objIntNumVali->isValid($fxVarsIntConductorClassId) === false ){
            // エラーフラグをON
            // 例外処理へ
            $intErrorType = 2;
            $strErrStepIdInFx="00000100";
            //
            $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-170003",array($objIntNumVali->getValidRule()));
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        unset($objIntNumVali);
        
        $objSLTxtVali = new SingleTextValidator(0,128,false);
        if( $objSLTxtVali->isValid($fxVarsStrLT4UBody) === false ){
            // エラーフラグをON
            // 例外処理へ
            $intErrorType = 2;
            $strErrStepIdInFx="00000200";
            //
            $aryErrMsgBody[] = $objMTS->getSomeMessage("ITABASEH-ERR-5720403",array($objSLTxtVali->getValidRule()));
        }
        unset($objSLTxtVali);
    

        #Symfony-nodeパラメータ整形
        $aryExecuteData = $fxVarsAryReceptData;
        $aryNodeData = nodeDateDecodeForedit($fxVarsStrSortedData);
        #'start','end','movement','call','parallel-branch','conditional-branch','merge','pause','blank'
    
        //バリデーションチェック----
        $strErrMsg="";
        //Conductorクラス名称
        $strConductorName = '';
        if( array_key_exists("conductor_name",$aryExecuteData) === true ){
            $strConductorName = $aryExecuteData["conductor_name"];
        }
        $objSLTxtVali = new SingleTextValidator(1,256,false);
        if( $objSLTxtVali->isValid($strConductorName) === false ){
            // エラーフラグをON
            // 例外処理へ
            $intErrorType = 2;
            $strErrMsg = $objMTS->getSomeMessage("ITABASEH-ERR-170000",array($objSLTxtVali->getValidRule()));
        }
        unset($objSLTxtVali);
        
        $strConductorTips = '';
        if( array_key_exists("note",$aryExecuteData) === true ){
            $strConductorTips = $aryExecuteData["note"];
        }
        $objMLTxtVali = new MultiTextValidator(0,4000);
        if( $objMLTxtVali->isValid($strConductorTips) === false ){
            // エラーフラグをON
            // 例外処理へ
            $intErrorType = 2;
            $strErrMsg = $objMTS->getSomeMessage("ITABASEH-ERR-5721203",array($objMLTxtVali->getValidRule()));
        }
        unset($objMLTxtVali);

        if( $strErrMsg != "" ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000300";
            $intErrorType = 2;
            $strExpectedErrMsgBodyForUI = $strErrMsg;#implode("\n",$strErrMsg);
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }

        //接続先判定用ノードのリスト作成
        $arrNodeChkList=array();
        foreach ($aryNodeData as $key => $value) {
            $arrNodeChkList[]=$value['id'];
        }

        //各ノードの接続状態
        foreach ($aryNodeData as $key => $value) {
            if( isset($value['terminal']) ){
                foreach ( $value['terminal'] as $terminalname => $terminalnameinfo) {
                    //ノードの接続、接続先の有無判定
                    if( isset($terminalnameinfo['targetNode']) ){
                        if( $terminalnameinfo['targetNode'] == "" ){
                            $strErrMsg=$objMTS->getSomeMessage("ITABASEH-ERR-170001");
                        }else{
                            if( array_search( $terminalnameinfo['targetNode'], $arrNodeChkList) === false ){
                                 $strErrMsg=$objMTS->getSomeMessage("ITABASEH-ERR-170002");
                            }
                        }
                    }else{
                        $strErrMsg=$objMTS->getSomeMessage("ITABASEH-ERR-170002");                    
                    }
                }
            }else{
                $strErrMsg=$objMTS->getSomeMessage("ITABASEH-ERR-170002");
            }

            if( $strErrMsg != "" ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00000300";
                $intErrorType = 2;
                    $strExpectedErrMsgBodyForUI = $strErrMsg;
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            } 
        }


        //各ノードの備考
        foreach ($aryNodeData as $key => $value) {
            $strNodeNote = '';
            if( array_key_exists("note",$value) === true ){
                $strNodeNote = $value["note"];
            }
            $objMLTxtVali = new MultiTextValidator(0,4000);
            if( $objMLTxtVali->isValid($strNodeNote) === false ){
                // エラーフラグをON
                // 例外処理へ
                $intErrorType = 2;
                $strErrMsg = $objMTS->getSomeMessage("ITABASEH-ERR-5721203",array($objMLTxtVali->getValidRule()));
            }
            unset($objMLTxtVali);

            if( $strErrMsg != "" ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00000300";
                $intErrorType = 2;
                    $strExpectedErrMsgBodyForUI = $strErrMsg;
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            } 
        }

        //----バリデーションチェック


        $aryRetBody = checkNodeUseCaseValidate($aryNodeData);
    
        if( $aryRetBody[1] !== null ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000300";
            //
            $intErrorType = $aryRetBody[1];
            if( $aryRetBody[1] < 500 ){
                $strExpectedErrMsgBodyForUI = implode("\n",$aryRetBody[2]);
            }
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        
        
        // ----トランザクション開始
        $varTrzStart = $objDBCA->transactionStart();
        if( $varTrzStart === false ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000600";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        $boolInTransactionFlag = true;
        // トランザクション開始----
    
        // ----SYMPHONY、NODE、TERMINALクラスのCUR/JNLの、シーケンスを取得する（デッドロックを防ぐために、値昇順序））
           
        // ----NODE-CLASS-シーケンスを掴む
        $retArray = getSequenceLockInTrz('C_NODE_CLASS_MNG_JSQ','A_SEQUENCE');
        
        if( $retArray[1] != 0 ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000700";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
           
        $retArray = getSequenceLockInTrz('C_NODE_CLASS_MNG_RIC','A_SEQUENCE');
        
        if( $retArray[1] != 0 ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000800";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        
        // ----TERMINAL-CLASS-シーケンスを掴む
        $retArray = getSequenceLockInTrz('C_NODE_TERMINALS_CLASS_MNG_JSQ','A_SEQUENCE');
        
        if( $retArray[1] != 0 ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000700";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }

        $retArray = getSequenceLockInTrz('C_NODE_TERMINALS_CLASS_MNG_RIC','A_SEQUENCE');
        
        if( $retArray[1] != 0 ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000800";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }

        // ----SYM-CLASS-シーケンスを掴む
        $retArray = getSequenceLockInTrz('C_CONDUCTOR_CLASS_MNG_JSQ','A_SEQUENCE');
        
        if( $retArray[1] != 0 ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000900";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }

        $retArray = getSequenceLockInTrz('C_CONDUCTOR_CLASS_MNG_RIC','A_SEQUENCE');
        
        if( $retArray[1] != 0 ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00001000";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        // -SYM-CLASS-シーケンスを掴む----
        
        //----SYMPHONY、NODE、TERMINALクラスのCUR/JNLの、シーケンスを取得する（デッドロックを防ぐために、値昇順序））----
        
        // ----Conductorを登録

        if( $fxVarsIntConductorClassId == "" ){

            $register_tgt_row = $arySymClassValueTmpl;
            
            $retArray = getSequenceValueFromTable('C_CONDUCTOR_CLASS_MNG_RIC', 'A_SEQUENCE', FALSE );
            
            if( $retArray[1] != 0 ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00001100";
                //
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            else{
                $varRISeq = $retArray[0];
            }

            $varConductorClassNo = $varRISeq;
            $register_tgt_row['CONDUCTOR_CLASS_NO'] = $varRISeq;
            $register_tgt_row['CONDUCTOR_NAME']     = $aryExecuteData['conductor_name'];
            $register_tgt_row['DESCRIPTION']       = $aryExecuteData['note'];
            $register_tgt_row['DISUSE_FLAG']       = '0';
            $register_tgt_row['LAST_UPDATE_USER']  = $g['login_id'];
            
            $arrayConfigForIUD = $aryConfigForSymClassIUD;
            $tgtSource_row = $register_tgt_row;
            $sqlType = "INSERT";
        }else{

            $aryRetBody = $objOLA->getInfoOfOneConductor($fxVarsIntConductorClassId, 0);

            $aryRowOfSymClassTable=$aryRetBody[4];
            
            $fxVarsStrLT4UBody = 'T_'.$aryRetBody[4]['LUT4U'];
            
            //追い越しチェック　
            if( $fxVarsStrLT4UBody != 'T_'.$aryRowOfSymClassTable['LUT4U'] ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00001200";
                $intErrorType = 2;
                
                $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-5720305");
                
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );        
            }
            

            $varConductorClassNo = $fxVarsIntConductorClassId;
            $register_tgt_row['CONDUCTOR_CLASS_NO'] = $fxVarsIntConductorClassId;
            $register_tgt_row['CONDUCTOR_NAME']     = $aryExecuteData['conductor_name'];
            $register_tgt_row['DESCRIPTION']       = $aryExecuteData['note'];
            $register_tgt_row['DISUSE_FLAG']       = '0';
            $register_tgt_row['LAST_UPDATE_USER']  = $g['login_id'];
            
            $arrayConfigForIUD = $aryConfigForSymClassIUD;
            $tgtSource_row = $register_tgt_row;
            $sqlType = "UPDATE";

        }
        
        $retArray = makeSQLForUtnTableUpdate($g['db_model_ch']
                                            ,$sqlType
                                            ,"CONDUCTOR_CLASS_NO"
                                            ,"C_CONDUCTOR_CLASS_MNG"
                                            ,"C_CONDUCTOR_CLASS_MNG_JNL"
                                            ,$arrayConfigForIUD
                                            ,$tgtSource_row);

        if( $retArray[0] === false ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00001200";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        
        $sqlUtnBody = $retArray[1];
        $arrayUtnBind = $retArray[2];
        
        $sqlJnlBody = $retArray[3];
        $arrayJnlBind = $retArray[4];
        

        // ----履歴シーケンス払い出し
        $retArray = getSequenceValueFromTable('C_CONDUCTOR_CLASS_MNG_JSQ', 'A_SEQUENCE', FALSE );


        if( $retArray[1] != 0 ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00001300";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        else{
            $varJSeq = $retArray[0];
            $arrayJnlBind['JOURNAL_SEQ_NO'] = $varJSeq;
        }
        // 履歴シーケンス払い出し----
        
        $retArray01 = singleSQLCoreExecute($objDBCA, $sqlUtnBody, $arrayUtnBind, $strFxName);
        $retArray02 = singleSQLCoreExecute($objDBCA, $sqlJnlBody, $arrayJnlBind, $strFxName);

        if( $retArray01[0] !== true || $retArray02[0] !== true ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00001400";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        unset($retArray01);
        unset($retArray02);
        // Conductorを登録----

        // ----廃止nodeを取得、廃止
        if( $fxVarsIntConductorClassId !="" ){
            $strQuery = "SELECT"
                        ." * "
                        ." FROM "
                        ." C_NODE_CLASS_MNG "
                        ."WHERE "
                        ." DISUSE_FLAG IN ('0') "
                        ."AND CONDUCTOR_CLASS_NO = :CONDUCTOR_CLASS_NO "
                        ."ORDER BY "
                        ."NODE_CLASS_NO"
                        ."";

            $tmpDataSet = array();
            $tmpForBind = array();
            $tmpForBind['CONDUCTOR_CLASS_NO']=$fxVarsIntConductorClassId;
            
            $tmpRetBody = singleSQLExecuteAgent($strQuery, $tmpForBind, $strFxName);

            if( $tmpRetBody[0] === true ){
                $objQuery = $tmpRetBody[1];
                while($tmprow = $objQuery->resultFetch() ){
                    $tmpDataSet[]= $tmprow;
                }
                unset($objQuery);
                //$retBool = true;
            }else{
                $intErrorType = 500;
                $intRowLength = -1;
            }
            $aryMovement = $tmpDataSet;

            foreach($aryMovement as $aryDataForMovement){

                // ----ムーブメントを更新
                $register_tgt_row = array();
                $register_tgt_row['NODE_CLASS_NO']     = $aryDataForMovement['NODE_CLASS_NO'];
                $register_tgt_row['DISUSE_FLAG']       = '1';
                $register_tgt_row['LAST_UPDATE_USER']  = $g['login_id'];


                $tmparrayConfigForNodeClassIUD_2 = array(
                    "JOURNAL_SEQ_NO"=>"",
                    "JOURNAL_REG_DATETIME"=>"",
                    "JOURNAL_ACTION_CLASS"=>"",
                    "NODE_CLASS_NO"=>"",
                    "DISUSE_FLAG"=>"",
                    "LAST_UPDATE_TIMESTAMP"=>"",
                    "LAST_UPDATE_USER"=>""
                ); 

                $arrayConfigForIUD = $tmparrayConfigForNodeClassIUD_2;
                $tgtSource_row = $register_tgt_row;
                $sqlType = "UPDATE";

                $retArray = makeSQLForUtnTableUpdate($g['db_model_ch']
                                                    ,$sqlType
                                                    ,"NODE_CLASS_NO"
                                                    ,"C_NODE_CLASS_MNG"
                                                    ,"C_NODE_CLASS_MNG_JNL"
                                                    ,$arrayConfigForIUD
                                                    ,$tgtSource_row);
                

                if( $retArray[0] === false ){
                    // エラーフラグをON
                    // 例外処理へ
                    $strErrStepIdInFx="00001600";
                    //
                    throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                }
                
                $sqlUtnBody = $retArray[1];
                $arrayUtnBind = $retArray[2];
                
                $sqlJnlBody = $retArray[3];
                $arrayJnlBind = $retArray[4];
                
                // ----履歴シーケンス払い出し
                $retArray = getSequenceValueFromTable('C_NODE_CLASS_MNG_JSQ', 'A_SEQUENCE', FALSE );

                if( $retArray[1] != 0 ){
                    // エラーフラグをON
                    // 例外処理へ
                    $strErrStepIdInFx="00001700";
                    //
                    throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                }else{
                    $varJSeq = $retArray[0];
                    $arrayJnlBind['JOURNAL_SEQ_NO'] = $varJSeq;
                }
                // 履歴シーケンス払い出し----
                
                $retArray01 = singleSQLCoreExecute($objDBCA, $sqlUtnBody, $arrayUtnBind, $strFxName);
                $retArray02 = singleSQLCoreExecute($objDBCA, $sqlJnlBody, $arrayJnlBind, $strFxName);


                if( $retArray01[0] !== true || $retArray02[0] !== true ){
                    // エラーフラグをON
                    // 例外処理へ
                    $strErrStepIdInFx="00001800";
                    //
                    throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                }
                unset($retArray01);
                unset($retArray02);

                #terminal廃止
                if( $fxVarsIntConductorClassId !="" ){
                    $strQuery = "SELECT"
                                ." * "
                                ." FROM "
                                ." C_NODE_TERMINALS_CLASS_MNG "
                                ."WHERE "
                                ." DISUSE_FLAG IN ('0') "
                                ."AND CONDUCTOR_CLASS_NO = :CONDUCTOR_CLASS_NO "
                                ."AND NODE_CLASS_NO = :NODE_CLASS_NO " 
                                ."ORDER BY "
                                ."NODE_CLASS_NO"
                                ."";

                    $tmpDataSet = array();
                    $tmpForBind = array();
                    $tmpForBind['CONDUCTOR_CLASS_NO']=$fxVarsIntConductorClassId;
                    $tmpForBind['NODE_CLASS_NO']=$aryDataForMovement['NODE_CLASS_NO'];

                    $tmpRetBody = singleSQLExecuteAgent($strQuery, $tmpForBind, $strFxName);

                    if( $tmpRetBody[0] === true ){
                        $objQuery = $tmpRetBody[1];
                        while($tmprow = $objQuery->resultFetch() ){
                            $tmpDataSet[]= $tmprow;
                        }
                        unset($objQuery);
                        //$retBool = true;

                    }else{
                        $intErrorType = 500;
                        $intRowLength = -1;
                    }
                    $aryTerminals = $tmpDataSet;

                    foreach($aryTerminals as $aryDataForTerminal){

                        // ----ムーブメントを更新

                        $register_tgt_row = array();
                        $register_tgt_row['TERMINAL_CLASS_NO']     = $aryDataForTerminal['TERMINAL_CLASS_NO'];
                        $register_tgt_row['DISUSE_FLAG']       = '1';
                        $register_tgt_row['LAST_UPDATE_USER']  = $g['login_id'];

                        $arrayConfigForTermClassIUD2 = array(
                            "JOURNAL_SEQ_NO"=>"",
                            "JOURNAL_REG_DATETIME"=>"",
                            "JOURNAL_ACTION_CLASS"=>"",
                            "TERMINAL_CLASS_NO"=>"",
                            "DISUSE_FLAG"=>"",
                            "LAST_UPDATE_TIMESTAMP"=>"",
                            "LAST_UPDATE_USER"=>""
                        ); 

                        $arrayConfigForIUD = $arrayConfigForTermClassIUD2;
                        $tgtSource_row = $register_tgt_row;
                        $sqlType = "UPDATE";

                        $retArray = makeSQLForUtnTableUpdate($g['db_model_ch']
                                                            ,$sqlType
                                                            ,"TERMINAL_CLASS_NO"
                                                            ,"C_NODE_TERMINALS_CLASS_MNG"
                                                            ,"C_NODE_TERMINALS_CLASS_MNG_JNL"
                                                            ,$arrayConfigForIUD
                                                            ,$tgtSource_row);
                        

                        if( $retArray[0] === false ){
                            // エラーフラグをON
                            // 例外処理へ
                            $strErrStepIdInFx="00001600";
                            //
                            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                        }
                        
                        $sqlUtnBody = $retArray[1];
                        $arrayUtnBind = $retArray[2];
                        
                        $sqlJnlBody = $retArray[3];
                        $arrayJnlBind = $retArray[4];
                        
                        // ----履歴シーケンス払い出し
                        $retArray = getSequenceValueFromTable('C_NODE_TERMINALS_CLASS_MNG_JSQ', 'A_SEQUENCE', FALSE );

                        if( $retArray[1] != 0 ){
                            // エラーフラグをON
                            // 例外処理へ
                            $strErrStepIdInFx="00001700";
                            //
                            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                        }else{
                            $varJSeq = $retArray[0];
                            $arrayJnlBind['JOURNAL_SEQ_NO'] = $varJSeq;
                        }
                        // 履歴シーケンス払い出し----
                        
                        $retArray01 = singleSQLCoreExecute($objDBCA, $sqlUtnBody, $arrayUtnBind, $strFxName);
                        $retArray02 = singleSQLCoreExecute($objDBCA, $sqlJnlBody, $arrayJnlBind, $strFxName);
                        
 
                        if( $retArray01[0] !== true || $retArray02[0] !== true ){
                            // エラーフラグをON
                            // 例外処理へ
                            $strErrStepIdInFx="00001800";
                            //
                            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                        }
                        unset($retArray01);
                        unset($retArray02);
                    }
                }
            }
        }

        // 廃止nodeを取得、廃止----

        // ----nodeを登録
        $aryMovement  = $aryNodeData;

        foreach($aryMovement as $aryDataForMovement){

            // ----ムーブメントを更新
            $register_tgt_row = $aryNodeClassValueTmpl;
            
            $retArray = getSequenceValueFromTable('C_NODE_CLASS_MNG_RIC', 'A_SEQUENCE', FALSE );

            if( $retArray[1] != 0 ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00001500";
                //
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            else{
                $varRISeq = $retArray[0];
            }

            //個別オペレーションのチェック、取得
            if( !isset($aryDataForMovement['OPERATION_NO_IDBH']) )$aryDataForMovement['OPERATION_NO_IDBH']="";
            if( !isset($aryDataForMovement['PATTERN_ID']) )$aryDataForMovement['PATTERN_ID']="";
            if($aryDataForMovement['OPERATION_NO_IDBH'] != "")
            {
                $tmpStrOpeNoIDBH = $aryDataForMovement['OPERATION_NO_IDBH'];
                $tmpStrPatternID = $aryDataForMovement['PATTERN_ID'];
                $objIntNumVali = new IntNumValidator(null,null,"",array("NOT_NULL"=>true));
                if( $objIntNumVali->isValid($tmpStrOpeNoIDBH) === false ){
                    // エラーフラグをON
                    // 例外処理へ
                    $strErrStepIdInFx="00002600";
                    $intErrorType = 2;
                    $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-170004",array($tmpStrPatternID,$tmpStrOpeNoIDBH));
                    //
                    throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                }
                unset($objIntNumVali);
 
                $tmpAryRetBody = $objOLA->getInfoOfOneOperation($tmpStrOpeNoIDBH,1);

                if( $tmpAryRetBody[1] !== null ){
                    // エラーフラグをON
                    // 例外処理へ
                    $strErrStepIdInFx="00002700";
                    //
                    if( $tmpAryRetBody[1] == 101 ){
                        $intErrorType = 2;
                        //
                        $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-170005",array($tmpStrPatternID));
                        //
                        throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                    }
                }
            }

            if( !isset( $aryDataForMovement['CALL_CONDUCTOR_ID'] ) )$aryDataForMovement['CALL_CONDUCTOR_ID']="";
            if( !isset( $aryDataForMovement['note'] ) )$aryDataForMovement['note']="";
            if( !isset( $aryDataForMovement['NEXT_PENDING_FLAG'] ) )$aryDataForMovement['NEXT_PENDING_FLAG']="";
            if( !isset( $aryDataForMovement['DESCRIPTION'] ) )$aryDataForMovement['DESCRIPTION']="";
            if( !isset( $aryDataForMovement['ORCHESTRATOR_ID'] ) )$aryDataForMovement['ORCHESTRATOR_ID']="";
            if( !isset( $aryDataForMovement['PATTERN_ID'] ) )$aryDataForMovement['PATTERN_ID']="";
            if( !isset( $aryDataForMovement['OPERATION_NO_IDBH'] ) )$aryDataForMovement['OPERATION_NO_IDBH']="";
            if( !isset( $aryDataForMovement['SKIP_FLAG'] ) )$aryDataForMovement['SKIP_FLAG']="";
            if( !isset( $aryDataForMovement['NEXT_PENDING_FLAG'] ) )$aryDataForMovement['NEXT_PENDING_FLAG']="";

            if( !isset( $aryDataForMovement['x'] ) )$aryDataForMovement['x']="";
            if( !isset( $aryDataForMovement['y'] ) )$aryDataForMovement['y']="";
            if( !isset( $aryDataForMovement['w'] ) )$aryDataForMovement['w']="";
            if( !isset( $aryDataForMovement['h'] ) )$aryDataForMovement['h']="";

            //CALL呼び出し値有無
            if( $aryDataForMovement['type'] == "call" && ( $aryDataForMovement['CALL_CONDUCTOR_ID'] == "" || !is_numeric( $aryDataForMovement['CALL_CONDUCTOR_ID'] ) ) ){
                    $intErrorType = 2;
                    $strErrStepIdInFx="00002800";
                    $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-170006",array($fxVarsIntConductorClassId));
                    throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }

            //CALL呼び出しのループ簡易バリデーション（インスタンス実行時に詳細確認）
            if( $fxVarsIntConductorClassId != "" ){
                if ( $fxVarsIntConductorClassId == $aryDataForMovement['CALL_CONDUCTOR_ID']){
                    $intErrorType = 2;
                    $strErrStepIdInFx="00002800";
                    $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-170006",array($fxVarsIntConductorClassId));
                    throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                }
            }


            $varNodeClassID = $varRISeq;
            $register_tgt_row = array();
            $register_tgt_row['NODE_CLASS_NO']     = $varRISeq;
            $register_tgt_row['NODE_NAME']         = $aryDataForMovement['id'];
            $register_tgt_row['NODE_TYPE_ID']      = $aryDataForMovement['type'];            
            $register_tgt_row['ORCHESTRATOR_ID']   = $aryDataForMovement['ORCHESTRATOR_ID'];
            $register_tgt_row['PATTERN_ID']        = $aryDataForMovement['PATTERN_ID'];
            $register_tgt_row['CONDUCTOR_CALL_CLASS_NO']   = $aryDataForMovement['CALL_CONDUCTOR_ID'];
            $register_tgt_row['DESCRIPTION']       = $aryDataForMovement['note'];
            $register_tgt_row['CONDUCTOR_CLASS_NO'] = $varConductorClassNo;
            $register_tgt_row['OPERATION_NO_IDBH'] = $aryDataForMovement['OPERATION_NO_IDBH'];         
            $register_tgt_row['SKIP_FLAG'] = $aryDataForMovement['SKIP_FLAG'];         
            $register_tgt_row['NEXT_PENDING_FLAG'] = $aryDataForMovement['NEXT_PENDING_FLAG'];
            $register_tgt_row['DISUSE_FLAG']       = '0';
            $register_tgt_row['LAST_UPDATE_USER']  = $g['login_id'];

            $register_tgt_row['POINT_X']   = $aryDataForMovement['x'];
            $register_tgt_row['POINT_Y']   = $aryDataForMovement['y'];
            $register_tgt_row['POINT_W']   = $aryDataForMovement['w'];
            $register_tgt_row['POINT_H']   = $aryDataForMovement['h'];
            
            #変換
            if( $aryDataForMovement['type'] == "start" )            $register_tgt_row['NODE_TYPE_ID']=1;
            if( $aryDataForMovement['type'] == "end")               $register_tgt_row['NODE_TYPE_ID']=2;    
            if( $aryDataForMovement['type'] == "movement")          $register_tgt_row['NODE_TYPE_ID']=3;
            if( $aryDataForMovement['type'] == "call")              $register_tgt_row['NODE_TYPE_ID']=4;            
            if( $aryDataForMovement['type'] == "parallel-branch")   $register_tgt_row['NODE_TYPE_ID']=5;
            if( $aryDataForMovement['type'] == "conditional-branch")$register_tgt_row['NODE_TYPE_ID']=6;
            if( $aryDataForMovement['type'] == "merge")             $register_tgt_row['NODE_TYPE_ID']=7;
            if( $aryDataForMovement['type'] == "pause")             $register_tgt_row['NODE_TYPE_ID']=8;
            if( $aryDataForMovement['type'] == "blank")             $register_tgt_row['NODE_TYPE_ID']=9; 

            $arrayConfigForIUD = $arrayConfigForNodeClassIUD;
            $tgtSource_row = $register_tgt_row;
            $sqlType = "INSERT";

            $retArray = makeSQLForUtnTableUpdate($g['db_model_ch']
                                                ,$sqlType
                                                ,"NODE_CLASS_NO"
                                                ,"C_NODE_CLASS_MNG"
                                                ,"C_NODE_CLASS_MNG_JNL"
                                                ,$arrayConfigForIUD
                                                ,$tgtSource_row);

            if( $retArray[0] === false ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00001600";
                //
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            
            $sqlUtnBody = $retArray[1];
            $arrayUtnBind = $retArray[2];
            
            $sqlJnlBody = $retArray[3];
            $arrayJnlBind = $retArray[4];
            
            // ----履歴シーケンス払い出し
            $retArray = getSequenceValueFromTable('C_NODE_CLASS_MNG_JSQ', 'A_SEQUENCE', FALSE );

            if( $retArray[1] != 0 ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00001700";
                //
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            else{
                $varJSeq = $retArray[0];
                $arrayJnlBind['JOURNAL_SEQ_NO'] = $varJSeq;
            }
            // 履歴シーケンス払い出し----
            
            $retArray01 = singleSQLCoreExecute($objDBCA, $sqlUtnBody, $arrayUtnBind, $strFxName);
            $retArray02 = singleSQLCoreExecute($objDBCA, $sqlJnlBody, $arrayJnlBind, $strFxName);

            if( $retArray01[0] !== true || $retArray02[0] !== true ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00001800";
                //
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            unset($retArray01);
            unset($retArray02);      
            
            
            // ムーブメントを更新----

            // ----TERMINALを登録
            if( isset($aryDataForMovement['terminal']) ){
                $aryTerminals = $aryDataForMovement['terminal'];

                foreach($aryTerminals as $aryDataForTerminal){

                    $retArray = getSequenceValueFromTable('C_NODE_TERMINALS_CLASS_MNG_RIC', 'A_SEQUENCE', FALSE );

                    if( $retArray[1] != 0 ){
                        // エラーフラグをON
                        // 例外処理へ
                        $strErrStepIdInFx="00001500";
                        //
                        throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                    }
                    else{
                        $varRISeq = $retArray[0];
                    }

                    if( !isset( $aryDataForTerminal['case'] ) )$aryDataForTerminal['case']="";
                    if( !isset( $aryDataForTerminal['condition'] ) )$aryDataForTerminal['condition']="";
                    if( !isset( $aryDataForTerminal['x'] ) )$aryDataForTerminal['x']="";
                    if( !isset( $aryDataForTerminal['y'] ) )$aryDataForTerminal['y']="";

                    $register_tgt_row = array();
                    $register_tgt_row['TERMINAL_CLASS_NO']     = $varRISeq;
                    $register_tgt_row['TERMINAL_CLASS_NAME']   = $aryDataForTerminal['id'];
                    $register_tgt_row['TERMINAL_TYPE_ID']      = $aryDataForTerminal['type'];   
                    $register_tgt_row['NODE_CLASS_NO']         = $varNodeClassID;         
                    $register_tgt_row['CONDUCTOR_CLASS_NO']     = $varConductorClassNo;
                    $register_tgt_row['CONNECTED_NODE_NAME']   = $aryDataForTerminal['targetNode'];
                    $register_tgt_row['LINE_NAME']             = $aryDataForTerminal['edge'];
                    $register_tgt_row['TERMINAL_NAME']         = $aryDataForTerminal['id'];

                    //条件のstr化
                    $strterminalval="";
                    if(is_array($aryDataForTerminal['condition'])){
                        foreach ($aryDataForTerminal['condition'] as $tckey => $tcvalue) {
                            if($strterminalval == "" ){
                                $strterminalval = $tcvalue;
                            }else{
                                $strterminalval = $strterminalval .",". $tcvalue;
                            }
                        }
                        $register_tgt_row['CONDITIONAL_ID']        = $strterminalval;
                    }


                    $register_tgt_row['CASE_NO']               = $aryDataForTerminal['case'];         

                    $register_tgt_row['DISUSE_FLAG']       = '0';
                    $register_tgt_row['LAST_UPDATE_USER']  = $g['login_id'];

                    $register_tgt_row['POINT_X']   = $aryDataForTerminal['x'];
                    $register_tgt_row['POINT_Y']   = $aryDataForTerminal['y'];

                    if( $aryDataForTerminal['type'] == "in" )$register_tgt_row['TERMINAL_TYPE_ID']=1;
                    if( $aryDataForTerminal['type'] == "out")$register_tgt_row['TERMINAL_TYPE_ID']=2;


                    $arrayConfigForIUD = $arrayConfigForTermClassIUD;
                    $tgtSource_row = $register_tgt_row;
                    $sqlType = "INSERT";
                    
                    $retArray = makeSQLForUtnTableUpdate($g['db_model_ch']
                                                        ,$sqlType
                                                        ,"TERMINAL_CLASS_NO"
                                                        ,"C_NODE_TERMINALS_CLASS_MNG"
                                                        ,"C_NODE_TERMINALS_CLASS_MNG_JNL"
                                                        ,$arrayConfigForIUD
                                                        ,$tgtSource_row);
                    
                    if( $retArray[0] === false ){
                        // エラーフラグをON
                        // 例外処理へ
                        $strErrStepIdInFx="00001600";
                        //
                        throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                    }
                    
                    $sqlUtnBody = $retArray[1];
                    $arrayUtnBind = $retArray[2];
                    
                    $sqlJnlBody = $retArray[3];
                    $arrayJnlBind = $retArray[4];

                    // ----履歴シーケンス払い出し
                    $retArray = getSequenceValueFromTable('C_NODE_TERMINALS_CLASS_MNG_JSQ', 'A_SEQUENCE', FALSE );

                    if( $retArray[1] != 0 ){
                        // エラーフラグをON
                        // 例外処理へ
                        $strErrStepIdInFx="00001700";
                        //
                        throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                    }
                    else{
                        $varJSeq = $retArray[0];
                        $arrayJnlBind['JOURNAL_SEQ_NO'] = $varJSeq;
                    }
                    // 履歴シーケンス払い出し----

                    $retArray01 = singleSQLCoreExecute($objDBCA, $sqlUtnBody, $arrayUtnBind, $strFxName);
                    $retArray02 = singleSQLCoreExecute($objDBCA, $sqlJnlBody, $arrayJnlBind, $strFxName);

                    if( $retArray01[0] !== true || $retArray02[0] !== true ){
                        // エラーフラグをON
                        // 例外処理へ
                        $strErrStepIdInFx="00001800";
                        //
                        throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                    }
                    unset($retArray01);
                    unset($retArray02);   

                }
            }
            // TERMINALを登録----

        }
        // ムーブメントを登録----

        $aryRetBody = getPatternListWithOrchestratorInfo("",-1);
        if( $aryRetBody[1] !== null ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000200";
            $intErrorType = $aryRetBody[1];
            //
            $aryErrMsgBody = $aryRetBody[2];
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        $arrMVList = json_encode($aryRetBody[4]);

        // ----トランザクション終了
        $boolResult = $objDBCA->transactionCommit();
        if ( $boolResult === false ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00001700";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        $objDBCA->transactionExit();
        $boolInTransactionFlag = false;
        // トランザクション終了----

        $retBool = true;
        $intConductorClassId = $varConductorClassNo;
    }
    catch (Exception $e){
        //----トランザクション中のエラーの場合
        if( $boolInTransactionFlag === true){
            if( $objDBCA->transactionRollBack() === true ){
                $tmpMsgBody = $objMTS->getSomeMessage("ITABASEH-STD-102010");
            }
            else{
                $tmpMsgBody = $objMTS->getSomeMessage("ITABASEH-ERR-101030");
            }
            web_log($tmpMsgBody);
            
            // トランザクション終了
            if( $objDBCA->transactionExit() === true ){
                $tmpMsgBody = $objMTS->getSomeMessage("ITABASEH-STD-102020");
            }
            else{
                $tmpMsgBody = $objMTS->getSomeMessage("ITABASEH-ERR-101040");
            }
            web_log($tmpMsgBody);
            unset($tmpMsgBody);
        }
        //トランザクション中のエラーの場合----
        
        // エラーフラグをON
        if( $intErrorType === null ) $intErrorType = 500;
        $tmpErrMsgBody = $e->getMessage();
        if( 500 <= $intErrorType ) $strSysErrMsgBody = $objMTS->getSomeMessage("ITAWDCH-ERR-4011",array($strFxName,$tmpErrMsgBody));
        if( 0 < strlen($strSysErrMsgBody) ) web_log($strSysErrMsgBody);
        foreach($aryErrMsgBody as $strFocusErrMsg){
            web_log($strFocusErrMsg);
        }
    }
    $strResultCode = sprintf("%03d", $intErrorType);
    $strDetailCode = sprintf("%03d", $intDetailType);
    $retArray = array($strResultCode,
                      $strDetailCode,
                      $intConductorClassId,
                      nl2br($strExpectedErrMsgBodyForUI)
                      );
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-4",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    return $retArray;
}
//ある１のConductorの定義を新規登録（追加）する----


//----NODEの接続先（IN/OUT）のバリデーション
function checkNodeUseCaseValidate($aryNodeData){

    global $g;
    $retBool = false;
    $intErrorType = null;
    $aryErrMsgBody = array();
    $strErrMsg = "";
    
    $intControlDebugLevel01=250;
    
    $objMTS = $g['objMTS'];
    $objDBCA = $g['objDBCA'];
    
    $strFxName = '([FUNCTION]'.__FUNCTION__.')';
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-3",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    
    $strSysErrMsgBody = "";
    

    $arrNodeVariList=array();
    $arrNodeVariList['start']=array(
        "in"=>array(),
        "out"=>array('movement','call','parallel-branch','blank')
    );
    $arrNodeVariList['end']=array(
        "in"=>array('movement','call','conditional-branch','merge','pause','blank'),
        "out"=>array()
    );
    $arrNodeVariList['movement']=array(
        "in"=>array('start','movement','call','parallel-branch','conditional-branch','merge','pause','blank'),
        "out"=>array('end','movement','call','parallel-branch','conditional-branch','merge','pause','blank')
    );
    $arrNodeVariList['call']=array(
        "in"=>array('start','movement','call','parallel-branch','conditional-branch','merge','pause','blank'),
        "out"=>array('end','movement','call','parallel-branch','conditional-branch','merge','pause','blank')
    );
    $arrNodeVariList['parallel-branch']=array(
        "in"=>array('start','movement','call','conditional-branch','merge','pause','blank'),
        "out"=>array('movement','call','blank')
    );
    $arrNodeVariList['conditional-branch']=array(
        "in"=>array('movement','call'),
        "out"=>array('end','movement','call','parallel-branch','pause','blank')
    );
    $arrNodeVariList['merge']=array(
        "in"=>array('movement','call','pause','blank'),
        "out"=>array('end','movement','call','parallel-branch','pause','blank')
    );
    $arrNodeVariList['pause']=array(
        "in"=>array('movement','call','parallel-branch','merge','blank'),
        "out"=>array('end','movement','call','parallel-branch','merge','blank')
    );
    $arrNodeVariList['blank']=array(
        "in"=>array('start','movement','call','parallel-branch','conditional-branch','merge','pause','blank'),
        "out"=>array('start','movement','call','parallel-branch','conditional-branch','merge','pause','blank')
    );

    try{
        foreach ($aryNodeData as $key => $value) {
            $nodetype = $value['type'];
            $arrTerminal=array();
            foreach ($value['terminal'] as $tkey => $tvalue) {
                $arrTerminal[$tvalue['type']]=$tvalue['targetNode'];
            }

            foreach ($arrTerminal as $tkey => $tvalue) {
                $nodetype2 = $aryNodeData[$tvalue]['type'];

                $retNodeValidate = in_array($nodetype2,$arrNodeVariList[$nodetype][$tkey]);
                if( $retNodeValidate == false ){
                        $aryErrMsgBody[] = $objMTS->getSomeMessage("ITAWDCH-ERR-26000",array($nodetype));
                        $strErrStepIdInFx="00000300";
                        throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
                }

            }

        }
        $retBool = true;
    }
    catch(Exception $e){
        if( $intErrorType === null ) $intErrorType = 2;
        $tmpErrMsgBody = $e->getMessage();
        if( 500 <= $intErrorType ) $strSysErrMsgBody = $objMTS->getSomeMessage("ITAWDCH-ERR-4011",array($strFxName,$tmpErrMsgBody));
        if( 0 < strlen($strSysErrMsgBody) ) web_log($strSysErrMsgBody);
    }

    $retArray = array($retBool,$intErrorType,$aryErrMsgBody,$strErrMsg);
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-4",array(__FILE__,$strFxName)),$intControlDebugLevel01);

    return $retArray;

}
//NODEの接続先（IN/OUT）のバリデーション----


//----ある１のConductorのクラス定義を表示する
function printOneOfonductorClasses($fxVarsIntSymphonyClassId, $fxVarsIntMode){
    // グローバル変数宣言
    global $g;
    $arrayResult = array();
    $strResultCode = "";
    $strDetailCode = "";
    $intSymphonyClassId = "";
    $intMode = "";
    $strStreamOfMovements = "";
    $strStreamOfSymphony = "";
    $strExpectedErrMsgBodyForUI = "";
    
    $intControlDebugLevel01=250;
    
    $objMTS = $g['objMTS'];
    $objDBCA = $g['objDBCA'];
    
    $intErrorType = null;
    $intDetailType = null;
    $aryErrMsgBody = array();
    
    $strFxName = '([FUNCTION]'.__FUNCTION__.')';
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-3",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    
    $strSysErrMsgBody = "";
    try{
        require_once($g['root_dir_path']."/libs/commonlibs/common_ola_classes.php");
        $objOLA = new OrchestratorLinkAgent($objMTS,$objDBCA);
        //----Conductorが存在するか？
        
        //----バリデーションチェック(入力形式)
        $objIntNumVali = new IntNumValidator(null,null,"",array("NOT_NULL"=>true));
        if( $objIntNumVali->isValid($fxVarsIntSymphonyClassId) === false ){
            // エラーフラグをON
            // 例外処理へ
            $intErrorType = 2;
            $strErrStepIdInFx="00000100";
            //
            $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-170000",array($objIntNumVali->getValidRule()));
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        unset($objIntNumVali);
        $intSymphonyClassId = $fxVarsIntSymphonyClassId;

        $objIntNumVali = new IntNumValidator(null,null,"",array("NOT_NULL"=>true));
        if( $objIntNumVali->isValid($fxVarsIntMode) === false ){
            // エラーフラグをON
            // 例外処理へ
            $intErrorType = 2;
            $strErrStepIdInFx="00000200";
            //
            $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-170007",array($objIntNumVali->getValidRule()));
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        unset($objIntNumVali);
        $intMode = $fxVarsIntMode;
        //バリデーションチェック(入力形式)----
        
        //----symphony_ins_noごとに作業パターンの流れを収集する
        //----バリデーションチェック(実質評価)
        $aryRetBody = $objOLA->getInfoFromOneOfConductorClass($fxVarsIntSymphonyClassId, 0);
        if( $aryRetBody[1] !== null ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000300";
            //
            if( $aryRetBody[1] === 101 ){
                $intErrorType = 2;
                //
                $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-170008");
            }
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        //バリデーションチェック(実質評価)----
        $aryRowOfSymClassTable = $aryRetBody[4];
        $aryRowOfMovClassTable = $aryRetBody[5];
        
        //----オーケストレータ情報の収集
        
        $aryRetBody = $objOLA->getLiveOrchestratorFromMaster();
        
        if( $aryRetBody[1] !== null ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000400";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        $aryOrcListRow = $aryRetBody[0];
        
        $aryPatternListPerOrc = array();
        //----存在するオーケスト—タ分回る
        foreach($aryOrcListRow as $arySingleOrcInfo){
            $varOrcId = $arySingleOrcInfo['ITA_EXT_STM_ID'];
            $varOrcRPath = $arySingleOrcInfo['ITA_EXT_LINK_LIB_PATH'];
            
            $objOLA->addFuncionsPerOrchestrator($varOrcId,$varOrcRPath);
            $aryRetBody = $objOLA->getLivePatternList($varOrcId);
            if( $aryRetBody[1] !== null ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00000500";
                //
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            $aryRow = $aryRetBody[0];
            
            //----オーケストレータカラーを取得
            $aryRetBody = $objOLA->getThemeColorName($varOrcId);
            if( $aryRetBody[1] !== null ){
                // エラーフラグをON
                // 例外処理へ
                $strErrStepIdInFx="00000600";
                //
                throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
            }
            $strThemeColor = $aryRetBody[0];
            //オーケストレータカラーを取得----
            
            $aryPatternListPerOrc[$varOrcId]['ThemeColor'] = $strThemeColor;
        }
        //存在するオーケスト—タ分回る----
        
        //オーケストレータ情報の収集----
        
        //----作業パターンの収集
        
        $aryRetBody = $objOLA->getLivePatternFromMaster();
        if( $aryRetBody[1] !== null ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000700";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        $aryPatternList = $aryRetBody[0];
        
        //作業パターンの収集----
        

        //----Conductor-Node-TerminalクラスのJSON形式の取得
        
        $aryRetBody = $objOLA->convertConductorClassJson($fxVarsIntSymphonyClassId);

        if( $aryRetBody[1] !== null ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000700";
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        $strnodeJson = $aryRetBody[5];
        
        //Conductor-Node-TerminalクラスのJSON形式の取得----

        $strLT4UBody = '';
        if( 0 < strlen($aryRowOfSymClassTable['LUT4U']) ){
            $strLT4UBody = 'T_'.$aryRowOfSymClassTable['LUT4U'];
        }
        

    }
    catch (Exception $e){
        // エラーフラグをON
        if( $intErrorType === null ) $intErrorType = 500;
        $tmpErrMsgBody = $e->getMessage();
        if( 500 <= $intErrorType ) $strSysErrMsgBody = $objMTS->getSomeMessage("ITAWDCH-ERR-4011",array($strFxName,$tmpErrMsgBody));
        if( 0 < strlen($strSysErrMsgBody) ) web_log($strSysErrMsgBody);
        foreach($aryErrMsgBody as $strFocusErrMsg){
            web_log($strFocusErrMsg);
        }
    }
    //
    $strResultCode = sprintf("%03d", $intErrorType);
    $strDetailCode = sprintf("%03d", $intDetailType);
    $arrayResult = array($strResultCode,
                         $strDetailCode,
                         $intSymphonyClassId,
                         $intMode,
                         $strnodeJson,
                         $strLT4UBody,
                         nl2br($strExpectedErrMsgBodyForUI)
                         );
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-4",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    return $arrayResult;
}
//ある１のConductorのクラス定義を表示する----


//----Movement一覧のリスト
function printPatternListForEditJSON($fxVarsStrFilterData){
    // グローバル変数宣言
    global $g;
    $arrayResult = array();
    $strResultCode = "";
    $strDetailCode = "";
    $strPatternListStream = "";
    $strExpectedErrMsgBodyForUI = "";
    
    $intControlDebugLevel01=250;
    
    $objMTS = $g['objMTS'];
    $objDBCA = $g['objDBCA'];
    
    $intErrorType = null;
    $intDetailType = null;
    $aryErrMsgBody = array();
    
    $strFxName = '([FUNCTION]'.__FUNCTION__.')';
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-3",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    
    $strSysErrMsgBody = "";
    
    //----オーケストレータ—ごとに作業パターンを収集する
    try{
        //----バリデーションチェック(入力形式)
        $objSLTxtVali = new SingleTextValidator(0,256,false);
        if( $objSLTxtVali->isValid($fxVarsStrFilterData) === false ){
            // エラーフラグをON
            // 例外処理へ
            $intErrorType = 2;
            $strErrStepIdInFx="00000100";
            
            $strExpectedErrMsgBodyForUI = $objMTS->getSomeMessage("ITABASEH-ERR-5720102",array($objSLTxtVali->getValidRule()));
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        unset($objSLTxtVali);
        //バリデーションチェック(入力形式)----
        
        $aryRetBody = getPatternListWithOrchestratorInfo($fxVarsStrFilterData,0);
        
        if( $aryRetBody[1] !== null ){
            // エラーフラグをON
            // 例外処理へ
            $strErrStepIdInFx="00000200";
            $intErrorType = $aryRetBody[1];
            //
            $aryErrMsgBody = $aryRetBody[2];
            //
            throw new Exception( $strFxName.'-'.$strErrStepIdInFx.'-([FILE]'.__FILE__.',[LINE]'.__LINE__.')' );
        }
        $aryListSource = array_values($aryRetBody[4]);

        $strPatternListStream = json_encode($aryListSource,JSON_UNESCAPED_UNICODE);
    }
    catch (Exception $e){
        // エラーフラグをON
        if( $intErrorType === null ) $intErrorType = 500;
        $tmpErrMsgBody = $e->getMessage();
        if( 500 <= $intErrorType ) $strSysErrMsgBody = $objMTS->getSomeMessage("ITAWDCH-ERR-4011",array($strFxName,$tmpErrMsgBody));
        if( 0 < strlen($strSysErrMsgBody) ) web_log($strSysErrMsgBody);
        foreach($aryErrMsgBody as $strFocusErrMsg){
            web_log($strFocusErrMsg);
        }
    }
    $strResultCode = sprintf("%03d", $intErrorType);
    $strDetailCode = sprintf("%03d", $intDetailType);
    $arrayResult = array($strResultCode,
                         $strDetailCode,
                         $strPatternListStream,
                         nl2br($strExpectedErrMsgBodyForUI)
                         );
    dev_log($objMTS->getSomeMessage("ITAWDCH-STD-4",array(__FILE__,$strFxName)),$intControlDebugLevel01);
    
    return $arrayResult;
}
//Movement一覧のリスト----



?>

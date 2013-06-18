<?php

/*
 * Applique une feuille de style à un xml
 */
function parseXsl($tpl,$xml,$pParams=array()){
    global $FROG;
    static $xslDoc=null;
    static $xslFnCache=null;
    static $xslt=null;
    // Définir les répertoires contenant les feuilles de style
    $cst=get_defined_constants(true);
//    foreach($cst['user'] as $k=>$v){
//        if(strlen($k) >= 5 && substr_compare($k,'PATH_',0,5)==0){
//            tplAssign('TPL'.strtoupper(substr($k,5)),$v.'/tpl');
//        }
//    }
    // Le document XML
    $xmlDoc=new DomDocument();
    $rc=$xmlDoc->loadXML($xml);
    if($rc==false){
        throw new Exception('Loading XML principal document via loadXML()',500);
    }
       // Création du processor
        $xsl=file_get_contents
            ($tpl
            ,true   
            );
        $xslDoc=new DomDocument;
        $rc=$xslDoc->loadXML($xsl);
        if($rc==false){
            throw new Exception('Loading '.pathFile($FROG['TPL']['file'][$tpl]['f'],$_tpl_subdir).' XSL document via loadXML()',500);
        }
    
        // L'analyseur XSLT
        $xslt=new XSLTProcessor();
        
        // Autoriser les appels aux fonctions PHP dans une feuille XSL          
        $xslt->registerPHPFunctions();
        $xslt->importStyleSheet($xslDoc);
        
        $xslt->setParameter
         (''                                      // Namespace
          ,$pParams   // Tableau de paramètres
         );
    $domHtmlText = $xslt->transformToXML($xmlDoc);
    //Correction d'un bug apparent qui supprime le caractère / dans la balise fermante /> meta
   // $domHtmlText = $domTranObj->saveXML();

    //Correction d'un bug apparent qui importe des xmlns="" dans les premieres balises des templates
    $domHtmlText =str_replace("xmlns=\"\"", "",$domHtmlText);
    return $domHtmlText;
}

<?php 
/**
 * Parse une chaine markdown
 */
function markdownToArray($markdown, $clefsAutorises) {
    $toutesLesLignes = explode('<br />', str_replace("\n", '', $markdown));
    $tableauDeRetour = array();
    
    $parametreCourant = null;
    
    foreach ($toutesLesLignes as $ligne) {
        if(isTitreMarkdown($ligne)) {
            $clefCourante = nettoieTitreMarkdown($ligne);
            if ( ! in_array($clefCourante, $clefsAutorises)) {
                $parametreCourant = null;
            } else {
                $parametreCourant = $clefCourante;
                $tableauDeRetour[$parametreCourant] = array();
            }
        }
        if($parametreCourant !== null && isParamMarkdown($ligne)) {
            $tableauDeRetour[$parametreCourant][] = getParam(nettoieTitreMarkdown($ligne));
        }
    }
    return $tableauDeRetour;
}


function isTitreMarkdown($string) {
    return (strpos($string, '# ') === 0);
}

function nettoieTitreMarkdown($string) {
    $string = preg_replace('#<a.+">#', '', $string);
    $string = preg_replace('#</a>#', '', $string);
    
    return substr($string, 2);
}

function getParam($string) {
    $exploded = explode(' : ', $string);
    if(!is_array($exploded) || count($exploded) !== 2) {
       return null;
    }
    
    return array('key' =>  $exploded[0], 'value' => $exploded[1]);
}



function isParamMarkdown($string) {
    return (strpos($string, '* ') === 0);
}




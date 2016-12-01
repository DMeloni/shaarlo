<?php

function filled($var){
	return !empty($var);
}

/**
 * Sort function.
 *
 * @param array $a
 * @param array $b
 *
 * @return int
 */
function triParDate($a,$b)
{
    if ($a['article_date'] == $b['article_date']) {
        return 0;
    }
    return ($a['article_date'] < $b['article_date']) ? -1 : 1;
}

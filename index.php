<?php
    if(!defined("BASE_DIR"))
        define("BASE_DIR", __DIR__ );

    include(BASE_DIR."/library/Rain/autoload.php");

    use Rain\Tpl;

    $config = array(
         "tpl_dir"       => "themes/default/",
         "cache_dir"     => "data/cache/tpl",
         "tpl_ext"       => "tpl"
    );
    Tpl::configure( $config );

    $t = new Tpl;

    //$t->assign('username','Memiks!');
    //$t->assign('dotsies','yes');
    
    $t->assign('channel_best',null);
    $t->assign('channel_item',null);
    
    
    try {
        if($_GET['index']=="true") {
            $t->draw('index');
        } else {
            $t->draw('header');
        }
    } catch (Exception $e) {
        echo "<pre>".$e."</pre>";
    }

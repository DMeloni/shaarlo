<?php 

require_once('Controller.class.php');

class OptionsStat extends Controller
{
    public function run() 
    {
        $mysqli = shaarliMyConnect();

            $options = array('extend', 'mode_river', 'display_empty_description', 
                'use_elevator', 'use_useless_options','use_dotsies',
                'use_top_buttons',
                'use_refresh_button',
                'display_rss_button',
                'display_bloc_conversation',
                'use_scroll_infini',
                'display_only_new_articles',
                'use_tipeee',
                'display_img',
                'display_only_unread',
                'display_little_img',
                'display_poussins',
            );
        
        
        $optionsStat = array();
        foreach ($options as $option) {
            $stats = getStatsFromOption($mysqli, $option);
            $nbActifs = $stats['true'];
            $nbDesactifs = $stats['false'];
            $ratio = $nbActifs / ($nbActifs+$nbDesactifs) * 100;
            if (($nbActifs+$nbDesactifs) > 5) {
                $optionsStat[] = array('nom' => $option, 'actifs' => $nbActifs, 'desactifs' => $nbDesactifs, 'ratio' => $ratio) ;
            }
        }
       
        $params = array();
        $this->render(
            array(
                  'optionsStat' => $optionsStat
            )
        );
    }

    public function render($params=array())
    {
        ?>
        <!doctype html>
        <html class="no-js" lang="en">
            <?php
            $this->renderHead();
            ?>
            <body>
                <?php
                $this->renderMenu();
                ?>

                <div class="row">
                    <div class="column large-12 text-center">
                        <h1>Statistiques des options</h1>
                        <div class="panel">
                                <div class="row top-orange ">
                                    <div class="column large-6">
                                        Option 
                                    </div>
                                    <!--<div class="column large-3">
                                        Nb d'activations
                                    </div>
                                    <div class="column large-3">
                                        Nb de désactivations
                                    </div>
                                    -->
                                    <div class="column large-6">
                                        % d'utilisation de l'option
                                    </div>
                                </div>
                                <hr/>
                            <?php
                            foreach ($params['optionsStat'] as $optionStat) {
                                ?>
                                <div class="row">
                                    <div class="column large-6">
                                        <?php echo $optionStat['nom'];?> 
                                    </div>
                                    <!--
                                    <div class="column large-3">
                                        <?php echo $optionStat['actifs'];?> 
                                    </div>
                                    <div class="column large-3">
                                        <?php echo $optionStat['desactifs'];?> 
                                    </div>
                                    -->
                                    <div class="column large-6">
                                        <?php 
                                            if($optionStat['ratio'] > 30) {
                                                ?><span class="color-success"><?php echo ceil($optionStat['ratio']);?> %</span><?php
                                            } else {
                                                ?><span class="red"><?php echo ceil($optionStat['ratio']);?> %</span><?php
                                            }
                                        ?>
                                        
                                    </div>
                                </div>
                                <hr/>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <?php
                $this->renderScript();
                ?>
            </body>
        </html>
            <?php
    }

    public static function renderScript($params = array())
    {
        ?>
        <?php
    }
}

$controller = new OptionsStat();
$controller->run();



<?php 

require_once('Controller.class.php');

class Dashboard extends Controller
{
    public function run() 
    {
        if (getUtilisateurId() === '') {
            header('Location: index.php');
            return;
        }
        
        $params = array();
        if (isset($_SESSION['username'])) {
            header(sprintf('Location: https://www.shaarli.fr/my/%s/', $_SESSION['pseudo']));
            return;
        }
        if ($this->post('action') == 'new') {
            // Maj url de son shaarli
            majShaarliUrl(sprintf('https://www.shaarli.fr/my/%s/', $this->post('pseudo')), true);
            header(sprintf('Location: https://www.shaarli.fr/my/%s/?do=login', $this->post('pseudo')));
            return;
        }

        $this->render($params);
    }

    public function render($params=array())
    {
        ?><!doctype html>
        <html class="no-js" lang="en">
            <?php
            $this->renderHead();
            ?>

            <body>
                <?php
                $this->renderMenu();
                ?>
                <?php
                if (isset($params['message'])) {
                    ?>
                    <div class="row">
                        <div class="columns large-12 center">
                            <div class="panel">
                            <span class="color-success"><?php echo $params['message'];?></span>
                            </div>
                        </div>
                    </div>
                    <?php
                }
                ?>
                <div class="row">
                    <div class="column large-12 text-center">
                        <h1>Service My</h1>
                    </div>
                </div>

                <div class="row">
                    <div class="columns large-12 center">
                        <div class="panel">
                            <div class="row">
                                <div class="columns large-12">
                                    <h3>Accès à un shaarli hebergé sur shaarli.fr</h3>
                                    <p>
                                    Le service My vous permet d'utiliser l'outil shaarli sans payer d'hébergement.
                                    </p>
                                    <hr/>
                                    <div class="row">
                                        <div class="column large-12">
                                           Choisissez un pseudo
                                        </div>
                                    </div>
                                    <div class="row">
                                        <form action="" method="POST">
                                            <div class="column large-5 left">
                                                <input name="pseudo" type="text" value="" placeholder="robocop, batman"/>
                                                <input name="action" value="new" type="hidden" />
                                            </div>
                                            <div class="column large-3 left">
                                                <input class="button tiny" type="submit" value="Continuer" />
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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
    public static function renderScript($params=array())
    {
        ?>
        <script>
            
            $(document).foundation();
           
        </script>
        <?php
    }
}

$dashboard = new Dashboard();
$dashboard->run();



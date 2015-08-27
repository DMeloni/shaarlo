<?php 

require_once('Controller.class.php');

class Abonnements extends Controller
{
    public function run() 
    {
        if (getUtilisateurId() === '') {
            header('Location: index.php');
            return;
        }
        
        getSession();
        $infoAboutAll = file_get_contents('http://shaarli.fr/api.php?do=getInfoAboutAll');
        $infoAboutAll = remove_utf8_bom($infoAboutAll);
        $infoAboutAllDecoded = json_decode($infoAboutAll, true);

        
        if (!empty($_POST)) {
            if (isset($_POST['shaarlistes'])) {
                $abonnements = $_POST['shaarlistes'];
            } else {
                $abonnements = array();
            }
            majAbonnements($abonnements);
        }
        
        $mesAbonnements = getAbonnements();
        
        if ($this->get('shaarliste')) {
            $shaarliste = $this->get('shaarliste');
            // Récupération en bdd
            $abonnements = getAbonnementsByShaarlieurId($shaarliste);
            
            // On filtre les abonnements à afficher dans le cas des abonnements d'une personne
            foreach ($infoAboutAllDecoded['stat'] as $s => $shaarli) {
                if(!in_array($shaarli['id'], $abonnements)) {
                    unset($infoAboutAllDecoded['stat'][$s]);
                }
            }
        } else {
            //Récupération dans la session
            $abonnements = $mesAbonnements;
            $shaarliste = getUtilisateurId();
        }
        $nbAbonnements = count($mesAbonnements);

        $isMe = false;
        if ($shaarliste === getUtilisateurId()) {
            $isMe = true;
        }
        
        $infoAboutAllDecodedChunked = array_chunk($infoAboutAllDecoded['stat'],  4);
        
        $this->render(
            array('nbAbonnements' => $nbAbonnements,
                  'infoAboutAllDecodedChunked' => $infoAboutAllDecodedChunked,
                  'abonnements' => $abonnements,
                  'mes_abonnements' => $mesAbonnements,
                  'shaarliste' => $shaarliste,
                  'is_me' => $isMe,
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
                        <h1>Abonnements de <?php eh($params['shaarliste']); ?></h1>
                    <p>
                    Sélectionnez les shaarlistes que vous souhaitez suivre.
                    </p>
                    <p>Vous suivez actuellement <span id="span-nbabonnements"><?php echo $params['nbAbonnements']; ?></span> shaarliste(s)</p>
                    </div>
                </div>
                <form id="form-abonnements" method="POST">
                <?php
                $this->renderMegaListeShaarlistes($params);
                ?>
                </form>
                <?php
                $this->renderScript();
                ?>
            </body>
        </html>
            <?php
    }

    public static function renderScript($params = array())
    {
        parent::renderScript();
        ?>
        <script>
            function addAbo(that, id, action) {
                var r = new XMLHttpRequest(); 
                var params = "do="+action+"&id=" + id;
                r.open("POST", "add.php", true); 
                r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                r.onreadystatechange = function () {
                    that.attr('data-waiting', 'false');
                    if (r.readyState == 4) {
                        if(r.status == 200){
                            var checkboxId = '#' + that.attr('data-shaarliste-id');
                            if(action == 'add') {
                                that.addClass('selected');
                                $(checkboxId).prop('checked', true);
                                $('#span-nbabonnements').text(parseInt($('#span-nbabonnements').text()) + 1);
                            }else {
                                $('#span-nbabonnements').text(parseInt($('#span-nbabonnements').text()) - 1);
                                $(checkboxId).prop('checked', false);
                                that.removeClass('selected');
                            }
                            return; 
                        }
                        else {
                            that.text = '-Erreur-';
                            return; 
                        }
                    }
                }; 
                r.send(params);
            }
            $('.a-add-abonnement').click(function() {
                var checkboxId = '#' + $(this).attr('data-shaarliste-id');
                
                if ($(this).attr('data-waiting') != 'true') {
                    $(this).attr('data-waiting', 'true');
                    if ($(checkboxId).is(':checked')) {
                        // L'utilisateur se désabonne
                        $(this).text("S'abonner");
                        addAbo($(this), $(this).attr('data-shaarliste-id'), 'delete');
                        $('#shaarliste-selection-' + $(this).attr('data-shaarliste-id')).addClass('not-selected');
                    } else {
                        $(this).text("Ne plus suivre");
                        $('#shaarliste-selection-' + $(this).attr('data-shaarliste-id')).removeClass('not-selected');
                        addAbo($(this), $(this).attr('data-shaarliste-id'), 'add');
                    }
                }
                return false;
            });

            
            $('#button-tous').click(function() {
                var checkbox = $('.checkbox-shaarliste');
                $.each( checkbox, function( key, value ) {
                    $(value).prop('checked', true);
                    var shaarlisteSelection = '#shaarliste-selection-' + $(value).attr('id');
                    $(shaarlisteSelection).addClass('selected');
                });
                $('#form-abonnements').submit();
            });

            $('#button-personne').click(function() {
                var checkbox = $('.checkbox-shaarliste');
                $.each( checkbox, function( key, value ) {
                    $(value).prop('checked', false);
                    var shaarlisteSelection = '#shaarliste-selection-' + $(value).attr('id');
                    $(shaarlisteSelection).removeClass('selected');
                });
                $('#form-abonnements').submit();
            });
            $(document).foundation();
           
        </script>
        <?php
    }
}

$controller = new Abonnements();
$controller->run();



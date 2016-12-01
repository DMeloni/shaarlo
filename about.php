<?php 

require_once('Controller.class.php');

class Dashboard extends Controller
{
	public function run() 
	{
		$params = array();
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
		        <div class="row">
		            <div class="column large-12 text-center">
		                <h1>A propos</h1>
		            </div>
		        </div>

		    	<div class="row">
		    		<div class="columns large-12 center">
		    			<div class="panel">
		    				<div class="row">
                                <div class="columns large-12 text-center">
                                    <img alt="logo-shaarli" src="css/img/logo-shaarli.png" />
                                </div>
                                <div class="columns large-12">
                                    <h3>C'est quoi shaarli ? </h3>
                                    <p>
                                    shaarli est un logiciel gratuit et open source qui permet de partager sans mal des liens internet.
                                    </p>
                                    <div class="row">
                                        <div class="column large-12">
                                           Vous pouvez télécharger la dernière version de shaarli <a target="_blank" href="https://github.com/shaarli/shaarli">ici</a> ou la version d'origine chez son auteur sebsauvage <a target="_blank" href="http://sebsauvage.net/wiki/doku.php?id=php:shaarli">ici</a>
                                        </div>
                                    </div>
                                </div>
		    				</div>
		    			</div>
		    		</div>
		    	</div>
                <div class="row">
                    <div class="columns large-12 center">
                        <div class="panel">
                            <div class="row">
                            <div class="columns large-12 text-center">
                                    <img alt="logo-shaarlo" src="css/img/logo-shaarlo.png" />
                                </div>
                                <div class="columns large-12">
                                    <h3>C'est quoi shaarli.fr ? </h3>
                                    <p>
                                    shaarli.fr est un site web qui affiche une partie des liens que les utilisateurs de shaarli veulent bien partager.
                                    </p>
                                    <p>
                                    Vous pouvez contacter l'administrateur par mail : <a href="mailto:contact@shaarli.fr">contact@shaarli.fr</a>
                                    </p>
                                    <hr/>
                                    <p>
                                    Des alternatives à shaarli.fr existent et sont appelées les River : 
                                    <a href="http://river.hoa.ro/">Arthur Hoaro</a> ou encore <a href="https://ecirtam.net/shaarlirss/">Oros</a>
                                    </p>
                                    <hr/>
                                    
                                    <p>
                                    Il existe une base de données assez complète des utilisateurs de shaarli : <a href="https://github.com/Oros42/shaarlis_list">Liste d'Oros</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="column large-12 text-center">
                        <h2 id="thanks">Remerciements</h2>
                        <div class="panel">
                            <div class="row">
                                <div class="column large-12">
                                    <img src="img/merci.gif" alt="merci.gif"/>
                                </div>
                                <div class="column large-12">
                                  Je souhaite remercier chaleureusement les personnes qui ont contribuées
                                  de près comme de loin à shaarlo !
                                  <br/>
                                  Je tiens à vous remercier également pour vos bons conseils au quotidien qui aident à améliorer l'usage de l'outil !
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column large-12">
                        <div class="panel">
                            <h3>Contributions Juillet 2015</h3>
                            <div class="row">
                                <div class="column large-12">
                                  Merci à toi Zouzou pour ton don de Juillet également !
                                </div>
                                <div class="column large-12">
                                  Je remercie évidemment de même toutes les personnes qui ont fait un don financier ou non :-)
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column large-12">
                        <div class="panel">
                            <h3>Contributions Juin 2015</h3>
                            <div class="row">
                                <div class="column large-12">
                                  Les personnes suivantes ont fait un don <a href="https://www.tipeee.com/shaarlo/">Tipeee</a> pour financer la maintenance et le développement du site et acceptent d'être citées :
                                </div>
                                <div class="column large-12">
                                    <ul>
                                      <li>Knah Tsaeb</li>
                                      <li>Zouzou</li>
                                      <li>streisand.fr</li>
                                    </ul>
                                </div>
                                <div class="column large-12">
                                  Je remercie également toutes les personnes qui ont fait un don et ne veulent pas nécessairement être citées !
                                </div>
                            </div>
                        </div>
                    </div>
                    <!--
                        Je tiens à remercier tinu qui me soutient tous les jours dans cette belle aventure !
                        Un bisou discret pour toi -> :*
                    -->
                </div>
                
                <div class="row">
                    <div class="columns large-12 center">
                        <div class="panel">
                            <div class="row">
                                <div class="columns large-12">
                                    <h3>Le jargon </h3>
                                    <p>
                                    Depuis sa création et son utilisation, les utilisateurs de shaarli ont adopté un language commun propre au logiciel et à la communauté, en voici les quelques définitions.
                                    </p>
                                    <ul>
                                        <li>Shaarlink : lien partagé via le logiciel Shaarli</li>
                                        <li>Shaarlier : action de partager un Shaarlink </li>
                                        <li>Shaarlieur/Shaarlieuse : personne ayant pour activité de Shaarlier</li>
                                        <li>Shaarliste : se dit également d'un Shaarlieur</li>
                                        <li>Shaarlibrairie : collection de site utilisant Shaarli </li>
                                        <li>Shaarlinker : synonyme de Shaarlier</li>
                                        <li>Shaarmi : ami d'un Shaarlieur étant lui-même Shaarlieur - se dit particulièrement lors d'une demande de conseil/d'info</li>
                                        <li>Shaarliscussion : discussion entre Shaarlieur par Shaarli interposé</li>
                                        <li>reShaarlier : Shaarlier à nouveau un Shaarlink</li>
                                        <li>Shaarlos : bande de plusieurs Shaarlieurs</li>
                                        <li>Shaare : note (ou lien) partagé via Shaarli - peut s'écrire aussi Shaar</li>
                                        <li>Shaaroulette : se promener sur son propre Shaarli ou celui de quelqu'un d'autre par sérendipité (voir <a href="http://orangina-rouge.org/shaarli/?cu3_FA">http://orangina-rouge.org/shaarli/?cu3_FA</a>) </li>
                                    </ul>
                                    <p>
                                     <a href="http://orangina-rouge.org/shaarli/?J-HBoA">Source</a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row" id="cout-infra">
                    <div class="columns large-12 center">
                        <div class="panel">
                            <div class="row">
                                <div class="columns large-12">
                                    <h3>Coût de l'infrastructure shaarli.fr </h3>
                                    <p>
                                    Pour un soucis de transparence, voici ce que coûte actuellement l'hébergement de shaarli.fr (2015)
                                    </p>
                                    <ul>
                                        <li>Hébergement mutualisé pro OVH : 60€</li>
                                        <li>Certificat SSL : 50€ </li>
                                        <li>Nom de domaine : 7€</li>
                                        <li>TVA : 24€</li>
                                        <li>Serveur VPS : 28€ (7€ x 4 trimestres)</li>
                                        <li>Total : 169€ TTC (14€ par mois)</li>
                                    </ul>
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
        parent::renderScript();
        ?>
        <script>
            
            $(document).foundation();
           
        </script>
        <?php
    }
}

$dashboard = new Dashboard();
$dashboard->run();



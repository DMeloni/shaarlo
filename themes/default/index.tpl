<html lang="fr">
    {include="header"}
    <body>
                <div id="menu-top"
                    {if="$menu_locked"}
                        class="menu position-fixed" onclick="scroll(0, 0);"
                    {else}
                        class="menu"
                    {/if}
                >
                    <h1>
                        <a href="/"><img class="logo hidden-on-smartphone" src="img/logo.png" height="40" width="36" /></a>
                        <a href="./index.php">{$title}</a>
                    </h1>
                    <ul>
                        <li><a href="index.php">Accueil</a></li>
                        <li><a href="index.php?sortBy=rand&amp;from=2000-09-16">Aléatoire</a></li>
                        <li><a href="my.php">My</a></li>
                        <li><a href="opml.php?mod=opml">OPML</a></li>

                        <!--<li><a href="https://nexen.mkdir.fr/shaarli-river/" id="river">Shaarli River</a></li>-->
                        
                        {if="$menu_locked"}
                            <li><span onclick="open_menu(this, 'menu-top');" 
                            class="pointer display-inline-block-text-bottom icon-lock" ></span></li>
                        {else}
                            <li><span onclick="lock_menu(this, 'menu-top');" 
                            class="pointer display-inline-block-text-bottom icon-open" ></span></li>
                        {/if}
                    </ul>
                </div>
                <div class="clear"/>
                <div id="header"
                    {if="$menu_locked"}
                        class="add-padding-top-8"
                    {/if}
                    >
                    <form method="GET" action="index.php" id="searchform"
                        {if="$filter_on == 'yes'"}
                            class="hidden"</xsl:attribute>"
                        {/if}
                        >
                        <input id="searchbar" type="text" name="q" placeholder="Rechercher un article" value="{$searchTerm}"/>
                        <input name="from" type="hidden" value="20130000" />
                        <input name="to" type="hidden" value="90130000" />
                    </form>
                     {if="!$afficher_messagerie"}
                     	{if="$filter_on == ''"}
                            <div class="options-extend">
                                <button onclick="option_extend(this)">(+)</button>
                            </div>
                        {/if}
                        <div class="pagination">
                            <div id="bloc-filtre"
		                     	{if="$filter_on == ''"}
		                     		class="hidden"
		                        {/if}
                            >
                                <form action="index.php" method="GET">
                                    <fieldset id="fielset-filtrer">
                                        <legend>Filtrer les articles :</legend>
                                        <label for="sortBy">Mot clef</label>
                                        <input type="text" name="q" placeholder="shaarli,linux,..." value="{$searchTerm}"/>
                                        <label for="from">Du</label>
                                        <input id="from" name="from" type="date" value="{$date_from}" />
                                        <label for="to">Au</label>
                                        <input id="to" name="to" type="date" value="{$date_to}" max="{$max_date_to}" />
                                        <label for="pop">Popularité</label>
                                        <input id="pop" name="pop" type="number" value="{$filtre_popularite}" min="0" />
                                        <label for="limit">Limite</label>
                                        <input id="limit" name="limit" type="number" min="0" max="{$max_limit}"
															{if="$limit !=''"}
                                                value={$limit}
															{else}
																value={$min_limit}
															{/if}
                                        />
                                    </fieldset>
                                    <fieldset>
                                        <legend>Options de tri :</legend>
                                        <label for="sortBy">Trier par</label>
                                        <select id="sortBy" name="sortBy">
                                            <option value="date"
																{if="$sortBy='date'"}
																	selected="selected"
																{/if}
                                            >Date</option>
                                            <option value="pop"
																{if="$sortBy='pop'"}
																	selected="selected"
																{/if}
                                             >Popularité</option>
                                        </select>
                                        <label for="sort">Par ordre</label>
                                        <select name="sort">
                                            <option value="desc"
																{if="$sort='desc'"}
																	selected="selected"
																{/if}
                                            		
                                             >Décroissant</option>
                                            <option value="asc"
  																{if="$sort='asc'"}
																	selected="selected"
																{/if}

                                             >Croissant</option>
                                        </select>
                                    </fieldset>
                                    <input id="valider" type="submit" value="Valider" />
                                </form>
                            </div>
                            <div class="clear"></div>
                            <div class="liens">
  											{if="$date_hier"}
												<a href="?from={$date_hier}000000&amp;to={$date_hier}235959">Jour précédent</a>
											{/if}
  											{if="$sort='asc'"}
												<a href="?from={$date_demain}000000&amp;to={$date_demain}235959"> / Jour suivant</a>
											{/if}
                            </div>
                        </div>
                     {/if}
                    <div class="clear"></div>
                </div>
                
                <div id="dashboard_icon" >
  							{if="$username"}
                        <a href="#" class="connected" onclick="showDashboard()">@</a>
							{else}
                        <a href="#" onclick="showDashboard()">@</a>
							{/if}
                </div>
                <div id="dashboard">
  						{if="$username"}
                        <div>
                            <h3>
                                <a href="?u={$username}">@{$pseudo}</a>
                            </h3>
                         </div>
                         <div>    
                            <ul>
                                <li><a href="{$my_shaarli}/">Mon shaarli</a></li>
                                <li><a href="?u=499f443cfd5481cc0a29db210ca208a5">Flux de @Shaarlo</a></li>
                            </ul>
                         </div>
                        <div>
                            <ul>
                                <li><a href="?do=logout">Se déconnecter</a></li>
                            </ul>
                        </div>
						{else}
                    <h4>Connecter mon shaarli</h4>
 
                    <form  method="POST" action="?" name="loadform" id="loadform">
                        <input id="shaarli" name="shaarli" tabindex="1" type="text"  value="" placeholder="http://domain.ext/shaarli"/>
                        <br/>
                        <input type="submit" value="Charger ma conf"/>
                    </form>
						{/if}
                    <div>
                        <small><a href="#" onclick="hideDashboard()">x Fermer cette vilaine fenêtre</a></small>
                    </div>
                </div>
						{loop="$channel_best"}
                    <div id="panel-best" class="panel panel-best add-box-shadow">
                    		{include="best"}
                    </div>
						{/loop}

                
						{if="$afficher_messagerie"}
							{loop="$channel_item"}
                        <div class="panel messagerie">
	                    		{include="item"}
                        </div>
							{/loop}
                    <div class="clear"></div>
						{else}
                    <div id="content">
                    		{include="mod_content_top"}
                    		{if="count($channel_item)==0"}
                            <div class="article add-box-shadow">
                                <h2 class=" article-title toptopic">Seul au monde</h2>
                                Pas de nouveaux shaarliens :(
                            </div>
                        {else}
									{loop="$channel_item"}
			                    		{include="item"}
									{/loop}
                        {/if}
                    		{include="mod_content_bottom"}
                    </div>
                    <div class="clear"></div>
						{/if}
                <div id="footer">
							{if="!$afficher_messagerie"}
                        <div class="pagination">
                            <div class="liens">
											{if="$date_hier"}
                                    <a href="?from={$date_hier}000000&amp;to={$date_hier}235959">Jour précédent</a>
                              	{/if}
											{if="$date_demain"}
                                    <a href="?from={$date_demain}000000&amp;to={$date_demain}235959"> / Jour suivant</a>
                              	{/if}
                            </div>
                            <div class="clear"></div>
                        </div>
                    {/if}
                    <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments - <a href="https://github.com/DMeloni/shaarlo">sources on github</a>. Ce site utilise vos cookies...</p></div>
                
                {if="$is_secure = 'no' and $wot = 'yes'"}
                    <script type="text/javascript">
                    var wot_rating_options = {
                    selector: ".wot"
                    };
                    </script>               
                    <script type="text/javascript" src="http://api.mywot.com/widgets/ratings.js"></script>
                {/if}

                
                {if="$username"}
                    <script>
                        function addAbo(that, id, action) {
                            var r = new XMLHttpRequest(); 
                            var params = "do="+action+"&amp;id=" + id;
                            r.open("POST", "add.php", true); 
                            r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            r.onreadystatechange = function () {
                                if (r.readyState == 4) {
                                    if(r.status == 200){
                                        if(action == 'add') {
                                            that.text = 'Se désabonner';
                                            that.innerHTML = 'Se désabonner';
                                            that.onclick = function () { addAbo(that, id, 'delete'); return false; };
                                        }else {
                                            that.text = 'Suivre';
                                            that.innerHTML = 'Suivre';
                                            that.onclick = function () { addAbo(that, id, 'add'); return false; };
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
                        
                        function validerLien(that, id, action) {
                            var r = new XMLHttpRequest(); 
                            var params = "do="+action+"&amp;id=" + id;
                            r.open("POST", "valide.php", true); 
                            r.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                            r.onreadystatechange = function () {
                                if (r.readyState == 4) {
                                    if(r.status == 200){
                                        if(action == 'valider') {
                                            that.text = 'Bloquer';
                                            that.innerHTML = 'Bloquer';
                                            that.onclick = function () { validerLien(that, id, 'bloquerLien'); return false; };
                                        }else {
                                            that.text = 'Valider';
                                            that.innerHTML = 'Valider';
                                            that.onclick = function () { validerLien(that, id, 'validerLien'); return false; };
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
                        

                        
                    </script>
               {/if}
            </body>
        </html>
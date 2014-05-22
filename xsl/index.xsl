<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl" exclude-result-prefixes="php">

    <xsl:output method="html" encoding="UTF-8"
        omit-xml-declaration="yes" indent="no"/>

    <xsl:param name="nb_sessions" />
    <xsl:param name="is_secure" />
    <xsl:param name="wot" />
    <xsl:param name="youtube" />
    <xsl:param name="my_shaarli" />
    <xsl:param name="my_respawn" />
    <xsl:param name="searchTerm" />
    <xsl:param name="mod_content_top" />
    <xsl:param name="mod_content_bottom" />
    <xsl:param name="rss_url" />
    <xsl:param name="next_previous" />
    <xsl:param name="date_demain" />
    <xsl:param name="date_hier" />
    <xsl:param name="when" />
    <xsl:param name="sort" />
    <xsl:param name="sortBy" />
    <xsl:param name="date_from" />
    <xsl:param name="date_to" />
    <xsl:param name="date_actual" />


    
    <xsl:template match="/">
		<html lang="fr">
			<head>
				<title>Shaarlo</title>
				<meta charset="utf-8"/>
				<meta name="description" content="" />
				<meta name="author" content="" />
				<meta name="viewport" content="width=device-width, user-scalable=yes" />
				<link rel="apple-touch-icon" href="favicon.png" />
				<meta name="apple-mobile-web-app-capable" content="yes" />
				<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />
				<link rel="shortcut icon" href="favicon.ico" />
				<link rel="stylesheet" href="css/style.css" type="text/css" media="screen"/>
				<link rel="alternate" type="application/rss+xml" href="{$rss_url}?do=rss" title="Shaarlo Feed" />
			</head>
			<body>
				<div id="header">
					<a href="index.php">Accueil</a>
					<a href="admin.php">Administration</a>
					<a href="archive.php">Archive</a>
					<a href="random.php">Aléatoire</a>
					<!--<a href="jappix/?r=shaarli@conference.dukgo.com" id="articuler">Articuler</a>
                    <a href="opml.php?mod=opml">OPML</a>-->
					<a href="https://nexen.mkdir.fr/shaarli-river/" id="river">Shaarli River</a>
                    <span id="compteur"><xsl:value-of select="$nb_sessions"/> personne(s) en ligne</span>
					<h1 id="top">
						<a href="./index.php"><xsl:value-of select="/rss/channel/title"/></a>
					</h1>
					<form method="GET" action="index.php">
						<input id="searchbar" type="text" name="q" placeholder="Rechercher un article" value="{$searchTerm}"/>
					</form>

                    <div class="pagination">
                        <div class="liens">
                            <xsl:if test="$date_hier">
                                <a href="?date={$date_hier}">Jour précédent</a>
                            </xsl:if>
                            <xsl:if test="$date_demain">
                                <a href="?date={$date_demain}"> / Jour suivant</a>
                            </xsl:if>
                        </div>
                        <div class="clear"/>
                        <xsl:if test="$searchTerm = '' ">
                            <div class="liens">
                                <form action="index.php" method="GET">
                                    <label for="sortBy">Trier par</label>
                                    <select name="sortBy">
                                        <option value="date">
                                            <xsl:if test="$sortBy='date'">
                                                <xsl:attribute name="selected">
                                                    selected
                                                </xsl:attribute>
                                            </xsl:if>
                                            Date</option>
                                        <option value="popularity">
                                            <xsl:if test="$sortBy='popularity'">
                                                <xsl:attribute name="selected">
                                                    selected
                                                </xsl:attribute>
                                            </xsl:if>
                                            Popularité</option>
                                    </select>
                                    <label for="sort">Par ordre</label>
                                    <select name="sort">
                                        <option value="desc">
                                            <xsl:if test="$sort='desc'">
                                                <xsl:attribute name="selected">
                                                    selected
                                                </xsl:attribute>
                                            </xsl:if>
                                            Décroissant</option>
                                        <option value="asc">
                                            <xsl:if test="$sort='asc'">
                                                <xsl:attribute name="selected">
                                                    selected
                                                </xsl:attribute>
                                            </xsl:if>
                                            Croissant</option>
                                    </select>
                                    <label for="from">Du</label>
                                    <input name="from" type="date" value="{$date_from}"></input>
                                    <label for="to">Au</label>
                                    <input name="to" type="date" value="{$date_to}"></input>
                                    <input type="submit" value="Trier" />
                                </form>
                            </div>
                        </xsl:if>

                        <div class="clear"/>
                    </div>
				</div>
				<div id="content">
					<xsl:value-of select="$mod_content_top" disable-output-escaping="yes"/>
                    <xsl:if test="count(/rss/channel/item) = 0">
                        <div class="article shaarli-youm-org">
                            <h2 class="	article-title toptopic">Seul au monde</h2>
					        Pas de nouveaux shaarliens :(
                        </div>
                    </xsl:if>
                    <xsl:if test="count(/rss/channel/item) != 0">
                        <xsl:apply-templates select="/rss/channel/item"/>
                    </xsl:if>
					<xsl:value-of select="$mod_content_bottom" disable-output-escaping="yes"/>					
				</div>
                <div class="pagination">
                    <div class="liens">
                        <xsl:if test="$date_hier">
                            <a href="?date={$date_hier}">Jour précédent</a>
                        </xsl:if>
                        <xsl:if test="$date_demain">
                            <a href="?date={$date_demain}"> / Jour suivant</a>
                        </xsl:if>
                    </div>
                    <div class="clear"/>
                </div>
				<div id="footer"> <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments - <a href="https://github.com/DMeloni/shaarlo">sources on github</a></p></div>
				
				<xsl:if test="$is_secure = 'no' and $wot = 'yes'">
					<script type="text/javascript">
					var wot_rating_options = {
					selector: ".wot"
					};
					</script>				
					<script type="text/javascript" src="http://api.mywot.com/widgets/ratings.js"></script>
				</xsl:if>

                <script>
                    function extend(him) {
                        console.log(him.parentNode.parentNode.childNodes[2]);
                        him.parentNode.parentNode.childNodes[2].style.maxHeight = '10000px';
                        him.style.display = 'none';
                    }

                </script>

			</body>
		</html>
    </xsl:template>
    
    <xsl:template match="item">
		<div class="article shaarli-youm-org">
			<xsl:if test="$next_previous = 'yes'">
				<div style="font-size:2em;">
					<a id="link{position()}" style="display:hidden;" href="#link{position()}" />
					<xsl:if test="(position()-1) &gt; 0">
						<a title="Lien précédent" style="text-decoration:none;" href="#link{position()-1}">&#171;</a>
					</xsl:if>
					<xsl:if test="(position()-1) &lt;= 0">
						<a title="Aller au dernier lien" style="text-decoration:none;" href="#link{count(/rss/channel/item)}">&#171;</a>
					</xsl:if>
					<xsl:if test="(position()+1) &lt;= count(/rss/channel/item)">
						<a title="Lien suivant" style="float:right;text-decoration:none;" href="#link{position()+1}">&#187;</a>
					</xsl:if>
					<xsl:if test="(position()+1) &gt; count(/rss/channel/item)">
						<a title="Aller au premier lien" style="float:right;text-decoration:none;" href="#link1">&#187;</a>
					</xsl:if>					
				</div>
			</xsl:if>
			<h2>
                <xsl:variable name="toptopic">
                    <xsl:call-template name="substring-count">
                        <xsl:with-param name="string" select="description" />
                        <xsl:with-param name="substr" select="'Permalink'" />
                    </xsl:call-template>
                </xsl:variable>
				<xsl:attribute name="class">
					article-title
					<xsl:if test="$toptopic &gt; 1"> toptopic</xsl:if>	
				</xsl:attribute> 
						
				<xsl:variable name="titrestring">
	                 <xsl:value-of select="title" />
				</xsl:variable>
								
				<xsl:variable name="titleencoded">
	                 <xsl:value-of select="php:function('urlencode', $titrestring)" />
				</xsl:variable>
	        
				<xsl:if test="$my_shaarli != ''" >
					<xsl:variable name="favourite">
						<xsl:call-template name="substring-count">
						  <xsl:with-param name="string" select="description" />
						  <xsl:with-param name="substr" select="$my_shaarli" />
						</xsl:call-template>
					</xsl:variable>				
					<a class="shaare" title="Partager sur Shaarli" target="_blank" href='{$my_shaarli}?post={link}&amp;source=bookmarklet&amp;title={$titleencoded}'>
					<xsl:attribute name="class">
						shaare
						<xsl:if test="$favourite &gt;= 1"> favourite</xsl:if>					
					</xsl:attribute>
					<xsl:if test="$favourite &gt;= 1"> ★</xsl:if>
					<xsl:if test="$favourite = 0"> ☆</xsl:if>	
					</a>
				</xsl:if>
				<xsl:if test="$my_respawn != ''" >
					<a class="shaare" title="Sauvegarder" target="_blank" href='{$my_respawn}?q={link}'>☉</a>
				</xsl:if>
				<a title="Go to original place" href="{link}" class="wot"><xsl:value-of select="title" /><xsl:if test="$toptopic &gt; 1"> [<xsl:value-of select="$toptopic" />]</xsl:if></a>
			</h2>
			<div>
                <xsl:if test="string-length(description) &gt;= 1500">
                    <xsl:attribute name="class">article-content extended</xsl:attribute>
                </xsl:if>
                <xsl:if test="string-length(description) &lt; 1500">
                    <xsl:attribute name="class">article-content</xsl:attribute>
                </xsl:if>

				<xsl:if test="$is_secure = 'no' and $youtube = 'yes'">
					<xsl:variable name="youtubevideoid">
						<xsl:if test="substring-after(link, 'youtube.com') != ''" >
							<xsl:if test="substring-before(substring-after(link, 'v='), '&amp;') != ''" >
								<xsl:value-of select="substring-before(substring-after(link, 'v='), '&amp;')" />
							</xsl:if>	
							<xsl:if test="substring-after(link, 'v=') != ''" >
								<xsl:value-of select="substring-after(link, 'v=')" />
							</xsl:if>	
							<xsl:if test="substring-after(link, 'v=') = ''" >
								<xsl:if test="substring-before(substring-after(link, 'embed/'), '?') != ''" >
									<xsl:value-of select="substring-before(substring-after(link, 'embed/'), '?')" />
								</xsl:if>	
							</xsl:if>											
						</xsl:if>	
					</xsl:variable>								
					<xsl:if test="$youtubevideoid != ''" >
					    <div class="wrapper">
						    <div class="h_iframe">
							<!-- a transparent image is preferable -->
							<img class="ratio" src="css/transparent.png"/>
							<iframe src="http://www.youtube.com/embed/{$youtubevideoid}" frameborder="0" allowfullscreen="allowfullscreen"></iframe>
						    </div>	
					    </div>	
					    <br/>
					</xsl:if>							 				    
				</xsl:if>

				<xsl:value-of select="description" disable-output-escaping="yes"/>
			</div>
            <div>
                <xsl:if test="category != ''" >
                    <span class="article-tag">Tags : <xsl:apply-templates select="category"/></span>
                </xsl:if>
            </div>
            <xsl:if test="string-length(description) &gt;= 1500">
                <div class="action-extend">
                    <button onclick="extend(this)">...</button>
                </div>
            </xsl:if>
		</div>    	
    </xsl:template>
    
	<xsl:template match="category">
		<xsl:call-template name="split">
			<xsl:with-param name="string">
				<xsl:value-of select="."/>
			</xsl:with-param>
		</xsl:call-template>
	</xsl:template>
	    
	<xsl:template name="split">
		<xsl:param name="string"/>
		<xsl:choose>
			<xsl:when test="contains($string,',')">
				<a href="index.php?q={substring-before($string,',')}&amp;type=category"><xsl:value-of select="substring-before($string,',')"/></a>,
				<xsl:call-template name="split">
					<xsl:with-param name="string">
						<xsl:value-of select="substring-after($string,',')"/>
					</xsl:with-param>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<a href="index.php?q={$string}&amp;type=category"><xsl:value-of select="$string"/></a>
			</xsl:otherwise>
		</xsl:choose>
	</xsl:template>
        
	<xsl:template name="substring-count">
	  <xsl:param name="string"/>
	  <xsl:param name="substr"/>
	  <xsl:choose>
	    <xsl:when test="contains($string, $substr) and $string and $substr">
	      <xsl:variable name="rest">
	        <xsl:call-template name="substring-count">
	          <xsl:with-param name="string" select="substring-after($string, $substr)"/>
	          <xsl:with-param name="substr" select="$substr"/>
	        </xsl:call-template>
	      </xsl:variable>
	      <xsl:value-of select="$rest + 1"/>
	    </xsl:when>
	    <xsl:otherwise>0</xsl:otherwise>
	  </xsl:choose>
	</xsl:template>
 
	<xsl:template name="urlencode">
		<xsl:param name="str" />
		<xsl:if test="$str">
			<xsl:variable name="first-char" select="substring($str,1,1)" />
			<xsl:choose>
				<xsl:when test="contains($safe,$first-char)">
					<xsl:value-of select="$first-char" />
				</xsl:when>
				<xsl:otherwise>
					<xsl:variable name="codepoint">
						<xsl:choose>
							<xsl:when test="contains($ascii,$first-char)">
								<xsl:value-of
									select="string-length(substring-before($ascii,$first-char)) + 32" />
							</xsl:when>
							<xsl:when test="contains($latin1,$first-char)">
								<xsl:value-of
									select="string-length(substring-before($latin1,$first-char)) + 160" />
							</xsl:when>
							<xsl:otherwise>
								<xsl:message terminate="no">
									Warning: string contains a character that is out of range!
									Substituting "?".
								</xsl:message>
								<xsl:text>63</xsl:text>
							</xsl:otherwise>
						</xsl:choose>
					</xsl:variable>
					<xsl:variable name="hex-digit1"
						select="substring($hex,floor($codepoint div 16) + 1,1)" />
					<xsl:variable name="hex-digit2"
						select="substring($hex,$codepoint mod 16 + 1,1)" />
					<xsl:value-of select="concat('%',$hex-digit1,$hex-digit2)" />
				</xsl:otherwise>
			</xsl:choose>
			<xsl:if test="string-length($str) > 1">
				<xsl:call-template name="url-encode">
					<xsl:with-param name="str" select="substring($str,2)" />
				</xsl:call-template>
			</xsl:if>
		</xsl:if>
	</xsl:template>        
</xsl:stylesheet>

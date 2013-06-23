<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0" xmlns:php="http://php.net/xsl">

    <xsl:output method="html" encoding="UTF-8"
        omit-xml-declaration="yes" indent="no" />
    
    <xsl:param name="wot" />
    <xsl:param name="my_shaarli" />
    <xsl:param name="searchTerm" />
    <xsl:param name="mod_content_top" />
    <xsl:param name="mod_content_bottom" />
    
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
				<link rel="alternate" type="application/rss+xml" href="http://shaarli.fr/rss" title="Shaarlo Feed" />
			</head>
			<body>
				<div id="header">
					<a href="index.php">Accueil</a>
					<a href="admin.php">Administration</a>
					<a href="archive.php">Archive</a>
					<h1 id="top">
						<a href="./index.php"><xsl:value-of select="/rss/channel/title"/></a>
					</h1>
					<form method="GET" action="index.php">
						<input id="searchbar" type="text" name="q" placeholder="Rechercher un article" value="{$searchTerm}"/>
					</form>
				</div>
				<div id="content">
					<xsl:value-of select="$mod_content_top" disable-output-escaping="yes"/>
					<xsl:apply-templates select="/rss/channel/item"/>
					<xsl:value-of select="$mod_content_bottom" disable-output-escaping="yes"/>					
				</div>
				<div id="footer"> <p>Please contact <a href="mailto:contact@shaarli.fr">me</a> for any comments - <a href="https://github.com/DMeloni/shaarlo">sources on github</a></p></div>
				
				<xsl:if test="$wot = 'yes'">
					<script type="text/javascript">
					var wot_rating_options = {
					selector: ".wot"
					};
					</script>				
					<script type="text/javascript" src="http://api.mywot.com/widgets/ratings.js"></script>
				</xsl:if>
			</body>
		</html>
    </xsl:template>
    
    <xsl:template match="item">
		<div class="article shaarli-youm-org">
			<h2>
				<xsl:attribute name="class">
					<xsl:variable name="toptopic">
						<xsl:call-template name="substring-count">
						  <xsl:with-param name="string" select="description" />
						  <xsl:with-param name="substr" select="'ermalink'" />
						</xsl:call-template>
					</xsl:variable>
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
				<a title="Go to original place" href="{link}" class="wot"><xsl:value-of select="title" /></a>
			</h2>
			<div class="article-content">
				<xsl:value-of select="description" disable-output-escaping="yes"/>
				<xsl:if test="category != ''" >
					<span class="article-tag">Tags : <xsl:apply-templates select="category"/></span>
				</xsl:if>
			</div>
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

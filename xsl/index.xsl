<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    >

    <xsl:output method="html" encoding="UTF-8"
        omit-xml-declaration="yes" indent="no" />
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
				<a title="Go to original place" href="{link}"><xsl:value-of select="title" /></a>
			</h2>
			<div class="article-content">
				<xsl:value-of select="description" disable-output-escaping="yes"/>
				<span class="article-tag">Tags : <xsl:apply-templates select="category"/></span>
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
        
</xsl:stylesheet>

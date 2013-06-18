<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    >

    <xsl:output method="xml" encoding="UTF-8"
        omit-xml-declaration="no" indent="no" />
    
    <xsl:template match="/">
		<rss version="2.0" >
			<channel>
				<title>Les discussions de Shaarli</title>
				<link>http://shaarli.fr/</link>
				<description>Shaarli Aggregators</description>
				<language>fr-fr</language>
				<copyright>http://shaarli.fr/</copyright>
				<xsl:apply-templates select="/rss/channel/item"/>	
			</channel>
		</rss>
    </xsl:template>

    <xsl:template match="item">
    	<item>
	    	<xsl:copy-of select="title" />
	    	<xsl:copy-of select="link" />
	    	<xsl:copy-of select="pubDate" />
	    	<xsl:copy-of select="category" />
			<xsl:copy-of select="description" />
		</item>
    </xsl:template>
</xsl:stylesheet>

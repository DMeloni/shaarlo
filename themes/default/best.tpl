    <xsl:template match="best">
        <xsl:if test="not(url_image = '')">
            <div class="article-image">
                <a title="Go to original place" href="{link}"><img src="{url_image}" width="450"/></a>
            </div>
        </xsl:if>
        <div class="article">
            <div class="article-mini-titre">
                <b>
                    <xsl:if test="$isToday">
                        En ce moment sur la shaarlisphère
                    </xsl:if>
                    <xsl:if test="not($isToday)">
                        A ce moment sur la shaarlisphère
                    </xsl:if>
                </b>
            </div>
            <h2 class=" article-title">
                <a title="Go to original place" href="{link}">
                    <xsl:value-of select="title" />
                </a> 
            </h2>
            <div class="mini hidden-on-smartphone visible-on-hover color-blue"><xsl:value-of select="link" /></div>
            <h4><xsl:value-of select="avatar" disable-output-escaping="yes"/> 
            <span class="entete-pseudo"><b><a href="{rss_url}"><xsl:value-of select="rss_titre" /></a></b></span>
            </h4>
            
            <div class="article-content"><xsl:value-of select="description"  disable-output-escaping="yes"/></div>
        </div>
    </xsl:template>

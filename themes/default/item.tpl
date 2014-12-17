    <xsl:template match="item" mode="standard">
        <xsl:variable name="toptopic">
            <xsl:call-template name="substring-count">
                <xsl:with-param name="string" select="description" />
                <xsl:with-param name="substr" select="'Permalink'" />
            </xsl:call-template>
        </xsl:variable>
        <xsl:if test="$toptopic >= $filtre_popularite">
            <div class="article {read_class} add-box-shadow ">
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
                        <a class="shaare" title="Copier ce lien" target="_blank" href='{$my_shaarli}/?post={link}&amp;source=bookmarklet&amp;title={$titleencoded}'>
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
                    <a title="Go to original place" href="{link}" class="wot" onmouseup="ireadit(this, '{id_commun}')"><xsl:value-of select="title" /><xsl:if test="$toptopic &gt; 1"> [<xsl:value-of select="$toptopic" />]</xsl:if></a>
                </h2>
                <div class="mini visible-on-hover color-blue"><xsl:value-of select="link" /></div>
                <div>
                    <xsl:if test="$extended and string-length(description) &gt;= 1500">
                        <xsl:attribute name="class">article-content extended</xsl:attribute>
                    </xsl:if>
                    <xsl:if test="not($extended) or string-length(description) &lt; 1500">
                        <xsl:attribute name="class">article-content</xsl:attribute>
                    </xsl:if>
                    
                    <!--<a title="Go to original place" href="{link}"><img src="./capture.php?url={link}" class="capture" width="240px" height="240px" /></a>-->
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
                    <xsl:if test="$no_description = ''">
                        <xsl:value-of select="description" disable-output-escaping="yes"/>
                    </xsl:if>
                </div>
                <div class="article-thumbnail">
                    <xsl:if test="url_image != ''" >
                        <a title="Go to original place" href="{link}" onmouseup="ireadit(this, '{id_commun}')">
                            <div style="background:url('{url_image}'); width:100%;height:200px;background-repeat: no-repeat;background-position: right;"></div>
                        </a>
                    </xsl:if>
                </div>
                <div>
                    <xsl:if test="category != ''" >
                        <span class="article-tag">Tags : <xsl:apply-templates select="category"/></span>
                    </xsl:if>
                </div>
                <div class="clear"/>
                <xsl:if test="$extended and string-length(description) &gt;= 1500 and $no_description = ''">
                    <div class="action-extend">
                        <a class="button-extend display-inline-block" onclick="extend(this)">+</a>
                    </div>
                </xsl:if>
            </div>
        </xsl:if>
    </xsl:template>
    <xsl:template match="item" mode="messagerie">
        <a href="?q=id:{id_commun}" class="add-padding-left-1 add-padding-bottom-1 add-padding-top-1 {read_class}">
            <div class="truncate display-inline-block-middle no-margin no-padding width-20">
                <img class="add-margin-right-1 entete-avatar" width="16" height="16" src="{dernier_auteur_favicon}"/>
                <span class="add-margin-right-2"><xsl:value-of select="dernier_auteur" /> (<xsl:value-of select="popularity" />)</span></div>
            <span class="float-right"><xsl:value-of select="derniere_date_maj" /></span>
            <div class="truncate display-inline-block-middle no-margin no-padding max-width-60"><span class="add-margin-right-2"><xsl:value-of select="title" /></span></div>
            
        </a>
        <hr class="no-margin"/>
    </xsl:template>

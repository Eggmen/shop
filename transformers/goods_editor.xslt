<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        xmlns:set="http://exslt.org/sets"
        extension-element-prefixes="set"
		version="1.0">

	<!-- доп атрибуты вкладки цен -->
	<xsl:template match="field[@type='tab'][@name='goods_multiprice'][ancestor::component[@type='form']]" mode="field_name">
		<li data-mode="prices" data-src="">
			<a href="#{generate-id(.)}"><xsl:value-of select="@title" /></a>
		</li>
	</xsl:template>

	<!-- внутренности вкладки цен -->
	<xsl:template match="field[@type='tab'][@name='goods_multiprice'][ancestor::component[@type='form']]" mode="field_content">
		<div id="{generate-id(.)}">
			<table class="gridTable" style="width: 100%;">
				<thead>
					<tr>
						<th><xsl:value-of select="$TRANSLATION[@const='TXT_MULTIPRICE_CATEGORY']"/></th>
						<xsl:for-each select="multiprices/currencies/currency">
						<th>
							<xsl:value-of select="$TRANSLATION[@const='TXT_MULTIPRICE_PRICE']"/>
							<xsl:text>, </xsl:text>
							<xsl:value-of select="."/>
						</th>
						</xsl:for-each>
						<th><xsl:value-of select="$TRANSLATION[@const='TXT_MULTIPRICE_MARGIN']"/></th>
					</tr>
				</thead>
				<tbody>
				<xsl:apply-templates select="multiprices/recordset/record"/>
				</tbody>
			</table>
		</div>
	</xsl:template>

	<xsl:template match="field[@type='tab'][@name='goods_multiprice']//record[ancestor::component[@type='form']]">
		<tr class="multiprice">
			<xsl:apply-templates select="." mode="field_name"/>
			<xsl:apply-templates select="." mode="field_content"/>
		</tr>
	</xsl:template>

	<xsl:template match="field[@type='tab'][@name='goods_multiprice']//record[ancestor::component[@type='form']]" mode="field_name">
		<td class="name">
			<label><xsl:value-of select="@title" disable-output-escaping="yes"/></label>
		</td>
	</xsl:template>

	<xsl:template match="field[@type='tab'][@name='goods_multiprice']//record[ancestor::component[@type='form']]" mode="field_content">

		<xsl:apply-templates select="field" mode="field_price"/>
		<xsl:apply-templates select="." mode="field_margin"/>
	</xsl:template>

	<xsl:template match="record[ancestor::component[@type='form']]/field" mode="field_price">
		<td class="control type_float" id="control_field_multiprice_{../@type_id}_{@currency_id}"
			data-multiprice="price" data-type="{../@type_id}" data-currency="{@currency_id}"
			data-rate="{@currency_rate}">
			<input class="text inp_float" type="text" name="{@name}" value="{.}" nrgn:pattern="{@pattern}" nrgn:message="{@message}" xmlns:nrgn="http://energine.org"/>
		</td>
	</xsl:template>

	<xsl:template match="record[ancestor::component[@type='form']]" mode="field_margin">
		<td class="control type_float" id="control_field_margin_{@type_id}" data-multiprice="margin">
			<input class="text inp_float" type="text" readonly="readonly" value="" data-margin="1"/>
		</td>
	</xsl:template>

    <xsl:template match="field[@name='smap_id' and ancestor::component[@class='GoodsEditor']]" mode="field_input">
        <select id="{@name}">
            <xsl:attribute name="name"><xsl:choose>
                <xsl:when test="@tableName"><xsl:value-of select="@tableName"/><xsl:if test="@language">[<xsl:value-of select="@language"/>]</xsl:if>[<xsl:value-of select="@name"/>]</xsl:when>
                <xsl:otherwise><xsl:value-of select="@name"/></xsl:otherwise>
            </xsl:choose></xsl:attribute>
            <xsl:call-template name="PRODUCTS_SMAP_SELECTOR">
                <xsl:with-param name="RECORDSET" select="recordset"/>
            </xsl:call-template>
        </select>
    </xsl:template>



    <xsl:template match="field[(@name='smap_features_multi') and (@type='multi') and (ancestor::component[@class='DivisionEditor'])]" mode="field_input">
        <xsl:variable name="DATA" select="options/option"/>
        <xsl:variable name="NAME"><xsl:choose>
            <xsl:when test="@tableName"><xsl:value-of select="@tableName"/><xsl:if test="@language">[<xsl:value-of select="@language"/>]</xsl:if>[<xsl:value-of select="@name"/>]</xsl:when>
            <xsl:otherwise><xsl:value-of select="@name"/></xsl:otherwise>
        </xsl:choose>[]</xsl:variable>
                <div class="checkbox_set">
                    <xsl:for-each select="set:distinct($DATA/@group_id)">
                        <xsl:variable name="GROUP_ID" select="."/>
                        <section>
                            <h4><xsl:value-of select="$DATA[@group_id =$GROUP_ID][1]/@group_name"/></h4>
                            <xsl:for-each select="$DATA[@group_id =$GROUP_ID]">
                                <div>
                                    <input type="checkbox" id="{generate-id(.)}" name="{$NAME}" value="{@id}" class="checkbox">
                                        <xsl:if test="@selected">
                                            <xsl:attribute name="checked">checked</xsl:attribute>
                                        </xsl:if>
                                    </input>
                                    <label for="{generate-id(.)}"><xsl:value-of select="."/></label>
                                </div>
                            </xsl:for-each>
                        </section>
                    </xsl:for-each>
                </div>
    </xsl:template>

    <xsl:template name="PRODUCTS_SMAP_SELECTOR">
        <xsl:param name="RECORDSET"/>
        <xsl:param name="LEVEL" select="0"/>
        <xsl:for-each select="$RECORDSET/record">
            <xsl:choose>
                <xsl:when test="field[@name='isLabel']=1">
                    <optgroup label="{field[@name='name']}"></optgroup>
                </xsl:when>
                <xsl:otherwise>
                    <option value="{field[@name='id']}">
                        <xsl:if test="field[@name='selected']=1">
                            <xsl:attribute name="selected">selected</xsl:attribute>
                        </xsl:if>
                        <xsl:call-template name="REPEATABLE">
                            <xsl:with-param name="STR"><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></xsl:with-param>
                            <xsl:with-param name="COUNT" select="$LEVEL"/>
                        </xsl:call-template>
                        <xsl:value-of select="field[@name='name']"/>
                        </option>

                </xsl:otherwise>
            </xsl:choose>
            <xsl:call-template name="PRODUCTS_SMAP_SELECTOR">
                <xsl:with-param name="RECORDSET" select="recordset"/>
                <xsl:with-param name="LEVEL"><xsl:value-of select="$LEVEL+2"/></xsl:with-param>
            </xsl:call-template>
        </xsl:for-each>
    </xsl:template>

    <xsl:template name="REPEATABLE">
        <xsl:param name="STR"/>
        <xsl:param name="COUNT"/>
        <xsl:param name="CURRENT" select="0"/>
        <xsl:if test="$CURRENT&lt;$COUNT">
            <xsl:value-of select="$STR" disable-output-escaping="yes"/>
            <xsl:call-template name="REPEATABLE">
                <xsl:with-param name="COUNT" select="$COUNT"/>
                <xsl:with-param name="STR" select="$STR"/>
                <xsl:with-param name="CURRENT"><xsl:value-of select="$CURRENT+1"/></xsl:with-param>
            </xsl:call-template>
        </xsl:if>
    </xsl:template>

</xsl:stylesheet>

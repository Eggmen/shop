<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
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


</xsl:stylesheet>

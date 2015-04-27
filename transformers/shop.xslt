<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  
    version="1.0">

	<xsl:template match="component[@class='GoodsList' and @type='list']">
		<div class="goods_list clearfix">
			<xsl:for-each select="recordset/record">
                <xsl:variable name="URL" ><xsl:value-of select="$BASE"/><xsl:value-of select="$LANG_ABBR"/><xsl:value-of
                        select="field[@name='smap_id']"/>view/<xsl:value-of
                        select="field[@name='goods_segment']"/>/</xsl:variable>
				<div class="goods_block">
					<div class="goods_image">
						<a href="{$URL}">
							<img src="{$RESIZER_URL}w200-h150/{field[@name='attachments']/recordset/record[1]/field[@name='file']}" alt="{field[@name='attachments']/recordset/record[1]/field[@name='title']}" />
						</a>
					</div>
					<div class="goods_name">
						<a href="{$URL}"><xsl:value-of select="field[@name='goods_name']" /></a>
					</div>
					<div class="goods_status available">
						<xsl:value-of select="field[@name='sell_status_id']/options/option[@selected]" />
					</div>
					<div class="goods_price">
						<xsl:value-of select="field[@name='goods_price']" />
					</div>
					<div class="goods_controls clearfix">
						<button type="button" class="buy_goods">BUY</button>
						<a href="#" class="add_to_wishlist">ADD_TO_WISHLIST</a>
					</div>
				</div>
			</xsl:for-each>
		</div>
	</xsl:template>

	<xsl:template match="component[@name='goodsList' and @componentAction='view']/recordset/record">
		<div class="goods_view clearfix">
			<div class="goods_image_block">
				<div class="goods_image">
					<img src="{field[@name='attachments']/recordset/record[1]/field[@name='file']}" alt="{field[@name='attachments']/recordset/record[1]/field[@name='name']}" />
				</div>
			</div>
			<div class="goods_info">
				<div class="goods_name">
					<xsl:value-of select="field[@name='goods_name']" />
				</div>
				<div class="goods_price">
					<xsl:value-of select="field[@name='goods_price']" />
				</div>
				<div class="goods_status available">
					<xsl:value-of select="field[@name='sell_status_id']/value" />
				</div>
				<div class="goods_buy">
					<button type="button" class="buy_goods">BUY</button>
				</div>
				<div class="goods_description">
					<xsl:value-of select="field[@name='goods_description_rtf']" disable-output-escaping="yes" />
				</div>
			</div>
			<div class="goods_features_block">
				<table class="goods_features">
					<thead>
						<tr>
							<th colspan="2">CHARACTERISTICS</th>
						</tr>
					</thead>
					<tbody>
						<xsl:for-each select="field[@name='features']/recordset/record">
							<xsl:if test="not(field[@name='feature_title'] = '')">
								<tr>
									<th><xsl:value-of select="field[@name='feature_title']" /></th>
									<td><xsl:value-of select="field[@name='feature_value']" /></td>
								</tr>
							</xsl:if>
						</xsl:for-each>
					</tbody>
				</table>
			</div>
		</div>
	</xsl:template>


</xsl:stylesheet>

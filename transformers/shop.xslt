<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"  
    version="1.0">

	<xsl:template match="component[@name='goodsList']">
		<div class="goods_list clearfix">
			<xsl:for-each select="recordset/record">
				<div class="goods_block">
					<div class="goods_image">
						<a href="{field[@name='goods_segment']}">
							<img src="{$BASE}{field[@name='attachments']/recordset/record[1]/field[@name='file']}" alt="{field[@name='attachments']/recordset/record[1]/field[@name='title']}" />
						</a>
					</div>
					<div class="goods_name">
						<a href="{field[@name='goods_segment']}"><xsl:value-of select="field[@name='goods_name']" /></a>
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


</xsl:stylesheet>

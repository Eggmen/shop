<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        version="1.0">
    <xsl:template match="component[@class='GoodsList' and @type='list']">
        <div class="products" id="{generate-id(recordset)}">
            <xsl:apply-templates/>
        </div>
    </xsl:template>

    <xsl:template match="toolbar[parent::component[@class='GoodsList'] and @name='list_type']">
        <div class="goods_view_type">
            <ul class="inline">
                <xsl:for-each select="control">
                    <li><xsl:apply-templates select="."/></li>
                </xsl:for-each>
            </ul>
        </div>
    </xsl:template>

    <xsl:template match="toolbar[parent::component[@class='GoodsList'] and @name='product']" />

    <xsl:template match="toolbar[parent::component[@class='GoodsList'] and @name='product']" mode="list">

        <div class="goods_controls clearfix">
            <ul class="inline wo-separator">
                <xsl:for-each select="control">
                    <li><xsl:apply-templates select="."/></li>
                </xsl:for-each>
                <!--<button type="button" class="buy_goods">BUY</button>
                <a href="#" class="add_to_wishlist">ADD_TO_WISHLIST</a>-->
            </ul>
        </div>
    </xsl:template>

    <xsl:template match="recordset[parent::component[@class='GoodsList' and @type='list']]">
    <div class="goods_list wide_list clearfix"> <!-- клас .wide_list для списка -->
        <xsl:for-each select="record">
            <xsl:variable name="URL">
                <xsl:value-of select="$BASE"/><xsl:value-of select="$LANG_ABBR"/><xsl:value-of
                    select="field[@name='smap_id']"/>view/<xsl:value-of
                    select="field[@name='goods_segment']"/>/</xsl:variable>
            <div class="goods_block">
	            <div class="goods_block_inner clearfix">
	                <div class="goods_image">
	                    <a href="{$URL}">
	                        <img src="{$RESIZER_URL}w200-h150/{field[@name='attachments']/recordset/record[1]/field[@name='file']}"
	                             alt="{field[@name='attachments']/recordset/record[1]/field[@name='title']}"/>
	                    </a>
	                </div>
                    <div class="goods_info">
                        <div class="goods_name">
                            <a href="{$URL}">
                                <xsl:value-of select="field[@name='goods_name']"/>
                            </a>
                        </div>
                        <div class="goods_producer">
                            <xsl:value-of select="field[@name='producer_id']/value"/>
                        </div>
                        <div class="goods_status available">
                            <xsl:value-of select="field[@name='sell_status_id']/value"/>
                        </div>
                        <div class="goods_price">
                            <xsl:value-of select="field[@name='goods_price']"/>
                        </div>
                        <xsl:apply-templates select="../../toolbar[@name='product']" mode="list"/>
                    </div>

	            </div>
            </div>
        </xsl:for-each>
    </div>
    </xsl:template>

	<xsl:template match="recordset[parent::component[(@class='GoodsList') and (@type='list') and (descendant::javascript/behavior/@name = 'ProductsCarousel')]]">
		<div id="{generate-id(.)}" class="carousel goods_list clearfix">
			<div class="carousel_viewbox">
				<div class="playlist_local">
					<xsl:for-each select="record">
						<xsl:variable name="URL">
							<xsl:value-of select="$BASE"/><xsl:value-of select="$LANG_ABBR"/><xsl:value-of
								select="field[@name='smap_id']"/>view/<xsl:value-of
								select="field[@name='goods_segment']"/>/</xsl:variable>
						<div class="item goods_block">
							<a href="{$URL}" class="goods_block_inner">
								<div class="goods_image">
									<img src="{$RESIZER_URL}w200-h150/{field[@name='attachments']/recordset/record[1]/field[@name='file']}"
									     alt="{field[@name='attachments']/recordset/record[1]/field[@name='title']}"/>
								</div>
								<div class="goods_name">
									<xsl:value-of select="field[@name='goods_name']"/>
								</div>
								<div class="goods_price">
									<xsl:value-of select="field[@name='goods_price']"/>
								</div>
							</a>
						</div>
					</xsl:for-each>
				</div>
			</div>
			<ul class="carousel_controls">
				<li class="carousel_control_previous">
					<a class="icon icon_carousel_control_previous previous fa fa-chevron-left" href="#" unselectable="on"></a>
				</li>
				<li class="carousel_control_next">
					<a class="icon icon_carousel_control_next next fa fa-chevron-right" href="#" unselectable="on"></a>
				</li>
			</ul>
		</div>
	</xsl:template>

    <xsl:template match="component[@class='GoodsSort']">
        <div class="goods_sort">
            <xsl:variable name="GET"><xsl:if test="@get!=''">?<xsl:value-of select="@get"/></xsl:if></xsl:variable>
            <xsl:variable name="TEMPLATE" select="@template"/>
            <xsl:variable name="RECORDS" select="recordset/record"/>
            <ul class="inline">
            <xsl:for-each select="$RECORDS/field[@name='field']/options/option">
                <li><a href="{$BASE}{$LANG_ABBR}{$TEMPLATE}sort-{@id}-{$RECORDS/field[@name='dir']/options/option[not(@selected)]/@id}/{$GET}"><xsl:value-of select="."/>
                    <xsl:if test="@selected">
                        <xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text>
	                    <i class="fa ficon_{$RECORDS/field[@name='dir']/options/option[@selected]}"></i>
                    </xsl:if>
                </a></li>
            </xsl:for-each>
            </ul>
        </div>
    </xsl:template>

    <xsl:template match="component[@class='GoodsFilter']">
        <form method="get" action="{$BASE}{$LANG_ABBR}{@template}{@action}">
            <xsl:apply-templates/>
        </form>
    </xsl:template>

    <xsl:template match="field[ancestor::component[@type='form'] and (@subtype='RANGE')]" mode="field_input">
        <span><xsl:value-of select="@text-from"/>:</span>
            <input class="text inp_filter"  type="text">
                <xsl:attribute name="name"><xsl:value-of select="@tableName"/>[<xsl:value-of select="@name"/>][begin]</xsl:attribute>
                <xsl:if test="@range-begin">
                    <xsl:attribute name="value"><xsl:value-of select="@range-begin"/></xsl:attribute>
                </xsl:if>
                <xsl:choose>
                    <xsl:when test="@range-min">
                        <xsl:attribute name="placeholder"><xsl:value-of select="@range-min"/></xsl:attribute>
                        <xsl:attribute name="min"><xsl:value-of select="@range-min"/></xsl:attribute>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:attribute name="placeholder">0</xsl:attribute>
                        <xsl:attribute name="min">0</xsl:attribute>
                    </xsl:otherwise>
                </xsl:choose>
            </input>
        <span style="padding-left:10px;"><xsl:value-of select="@text-to"/>:</span>
            <input class="text inp_filter" type="text">
                <xsl:attribute name="name"><xsl:value-of select="@tableName"/>[<xsl:value-of select="@name"/>][end]</xsl:attribute>
                <xsl:if test="@range-end">
                    <xsl:attribute name="value"><xsl:value-of select="@range-end"/></xsl:attribute>
                </xsl:if>
                <xsl:choose>
                    <xsl:when test="@range-max">
                        <xsl:attribute name="placeholder"><xsl:value-of select="@range-max"/></xsl:attribute>
                        <xsl:attribute name="max"><xsl:value-of select="@range-max"/></xsl:attribute>
                    </xsl:when>
                    <xsl:otherwise>
                        <xsl:attribute name="placeholder">0</xsl:attribute>
                    </xsl:otherwise>
                </xsl:choose>
            </input>
    </xsl:template>

	<xsl:template match="component[@class='GoodsList' and @type='form']/recordset/record">
		<div class="goods_view clearfix">
			<div class="goods_image_block">
				<div id="goodsGalleryLarge" class="single-item slider ">

							<xsl:for-each select="field[@name='attachments']/recordset/record">
								<div >
									<img src="{$RESIZER_URL}w400-h300/{field[@name='file']}" alt="{field[@name='name']}" />
								</div>
							</xsl:for-each>

					<!--<ul class="carousel_controls">
						<li class="carousel_control_previous">
							<a class="icon icon_carousel_control_previous previous fa fa-chevron-left" href="#" unselectable="on"></a>
						</li>
						<li class="carousel_control_next">
							<a class="icon icon_carousel_control_next next fa fa-chevron-right" href="#" unselectable="on"></a>
						</li>
					</ul>-->
				</div>
				<!--<div id="goodsGallerySmall" class="carousel goods_gallery_small">
					<div class="carousel_viewbox">
						<div class="playlist_local">
							<xsl:for-each select="field[@name='attachments']/recordset/record">
								<div class="goods_image">
									<img src="{$RESIZER_URL}w100-h75/{field[@name='file']}" alt="{field[@name='name']}" />
								</div>
							</xsl:for-each>
						</div>
					</div>
					<ul class="carousel_controls">
						<li class="carousel_control_previous">
							<a class="icon icon_carousel_control_previous previous fa fa-chevron-left" href="#" unselectable="on"></a>
						</li>
						<li class="carousel_control_next">
							<a class="icon icon_carousel_control_next next fa fa-chevron-right" href="#" unselectable="on"></a>
						</li>
					</ul>
				</div>-->
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

    <xsl:template match="control[ancestor::component[@class='GoodsFilter']]">
        <button id="{@id}" type="{@type}"><xsl:value-of select="@title"/></button>
    </xsl:template>

	<xsl:template match="component[@class='PageList' and @name='categoriesMenu']">
		<div class="categories_list clearfix">
			<xsl:for-each select="recordset/record">
				<div class="category">
					<a href="{field[@name='Segment']}">
						<div class="category_image">
							<div class="category_image_inner"> <!-- optional block for vertical align -->
								<img src="{field[@name='attachments']/recordset/record[1]/field[@name='file']}" alt="{field[@name='attachments']/recordset/record[1]/field[@name='name']}" />
							</div>
						</div>
						<div class="category_name">
							<xsl:value-of select="field[@name='Name']" />
						</div>
						<div class="category_description">
							<xsl:value-of select="field[@name='DescriptionRtf']" disable-output-escaping="yes" />
						</div>
					</a>
				</div>
			</xsl:for-each>
		</div>
	</xsl:template>

    <xsl:template match="component[@class='Categories']">
        <xsl:if test="not(@empty)">
                <xsl:apply-templates/>
        </xsl:if>
    </xsl:template>

    <xsl:template match="recordset[ancestor::component[@class='Categories']]">
        <ul class="main_menu clearfix">
            <xsl:apply-templates select="record"/>
        </ul>
    </xsl:template>

    <xsl:template match="record[ancestor::component[@class='Categories']]">
        <li class="main_menu_item">
            <xsl:attribute name="class">main_menu_item<xsl:if test="field[@name='Id']=$ID"> active</xsl:if></xsl:attribute>
            <a>
                <xsl:if test="$DOC_PROPS[@name='ID']!=field[@name='Id']">
                    <xsl:attribute name="href"><xsl:value-of select="$BASE"/><xsl:value-of select="$LANG_ABBR"/><xsl:value-of select="field[@name='Segment']"/></xsl:attribute>
                </xsl:if>
                <xsl:value-of select="field[@name='Name']"/>
            </a>
            <xsl:if test="recordset">
                <xsl:apply-templates select="recordset"/>
            </xsl:if>


        </li>
    </xsl:template>

</xsl:stylesheet>

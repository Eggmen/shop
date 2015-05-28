<?xml version="1.0" encoding="utf-8" ?>
<xsl:stylesheet
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
        version="1.0">
    <xsl:template match="component[(@class='Cart')]">
        <div id="{generate-id(recordset)}">
            <table border="1">
                <thead>
                <tr>
                    <th><xsl:text disable-output-escaping="yes">&amp;nbsp;</xsl:text></th>
                    <th><xsl:value-of select="recordset/record[1]/field[@name='goods_name']/@title"/></th>
                    <th><xsl:value-of select="recordset/record[1]/field[@name='cart_goods_count']/@title"/></th>
                    <th><xsl:value-of select="recordset/record[1]/field[@name='goods_price']/@title"/></th>
                    <th><xsl:value-of select="recordset/record[1]/field[@name='cart_goods_sum']/@title"/></th>
                </tr>
                </thead>
            <xsl:for-each select="recordset/record">
                <tr>
                    <td><img src="{$RESIZER_URL}w300-h200/{field[@name='attachments']/recordset/record[1]/field[@name='file']}" alt=""/></td>
                    <td><a href="{$BASE}{$LANG_ABBR}{field[@name='smap_id']}view/{field[@name='goods_segment']}/"><xsl:value-of
                            select="field[@name='goods_name']"/></a></td>
                    <td><xsl:value-of select="field[@name='cart_goods_count']"/></td>
                    <td><xsl:value-of select="field[@name='goods_price']"/></td>
                    <td><xsl:value-of select="field[@name='cart_goods_sum']"/></td>
                </tr>
            </xsl:for-each>
                <tfoot>
                    <tr>
                        <td colspan="4"><xsl:value-of select="recordset/record[1]/field[@name='cart_goods_sum']/@title"/>:</td>
                        <td><xsl:value-of select="sum(recordset/record/field[@name='cart_goods_sum'])"/></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </xsl:template>

    <xsl:template match="component[(@class='Cart') and (@componentAction='main')]">
        <div id="{generate-id(recordset)}" data-url="{$BASE}{$LANG_ABBR}{@single_template}{@action}">
            <strong><xsl:value-of select="@title"/>:
                <a href="{$BASE}{$LANG_ABBR}{@template}">
                    <xsl:value-of select="@count"/>
                </a>
            </strong>
        </div>
    </xsl:template>

</xsl:stylesheet>

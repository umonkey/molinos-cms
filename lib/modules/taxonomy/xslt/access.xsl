<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content" mode="content">
    <h2>Права на размещение документов в разделах</h2>
    <p>Укажите, какие группы пользователей в каких разделах могут размещать документы.</p>
    <p>Помните, что здесь вы управляете только видимостью разделов, права на создание и публикацию документов разных типов <a href="admin/structure/types">настраиваются отдельно</a>. Подробнее о настройке прав на разделы можно <a href="http://code.google.com/p/molinos-cms/wiki/mod_taxonomy">прочитать в документации</a>.</p>

    <xsl:variable name="groups" select="document(concat($api,'node/list.xml?class=group'))/nodes" />

    <form method="post" action="api/taxonomy/access.rpc">
      <table class="classic">
        <thead>
          <tr>
            <th>Раздел</th>
            <th>Публиковать могут</th>
          </tr>
        </thead>
        <tbody>
          <xsl:for-each select="document(concat($api,'taxonomy/access.xml'))/sections/section">
            <tr>
              <td>
                <a href="admin/node/{@id}?destination={$back}">
                  <xsl:attribute name="style">
                    <xsl:text>margin-left:</xsl:text>
                    <xsl:value-of select="(@level - 1) * 10" />
                    <xsl:text>px</xsl:text>
                  </xsl:attribute>
                  <xsl:value-of select="text()" />
                </a>
              </td>
              <td>
                <xsl:apply-templates select="$groups">
                  <xsl:with-param name="node" select="@id" />
                  <xsl:with-param name="current" select="@group" />
                </xsl:apply-templates>
              </td>
            </tr>
          </xsl:for-each>
        </tbody>
        <tfoot>
          <tr class="okbtn">
            <td colspan="2">
              <input type="submit" value="Сохранить" />
              <xsl:if test="$args/@destination">
                <xsl:text> или </xsl:text>
                <a href="{$args/@destination}">вернуться</a>
              </xsl:if>
            </td>
          </tr>
        </tfoot>
      </table>
    </form>
  </xsl:template>

  <xsl:template match="nodes">
    <xsl:param name="node" />
    <xsl:param name="current" />
    <select name="section[{$node}]">
      <xsl:for-each select="node">
        <option value="{@id}">
          <xsl:if test="@id=$current">
            <xsl:attribute name="selected">
              <xsl:text>selected</xsl:text>
            </xsl:attribute>
          </xsl:if>
          <xsl:value-of select="@name" />
        </option>
      </xsl:for-each>
    </select>
  </xsl:template>
</xsl:stylesheet>

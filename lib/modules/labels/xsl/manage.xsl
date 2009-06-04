<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content" mode="content">
    <xsl:variable name="node" select="document(concat($api,'node/get.xml?id=',/page/request/args/@id))/node" />

    <h2>
      <xsl:text>Управление меткой «</xsl:text>
      <xsl:value-of select="$node/@name" />
      <xsl:text>»</xsl:text>
    </h2>

    <form method="post">
      <table class="classic">
        <tbody>
          <xsl:for-each select="document(concat($api,'node/list.xml?limit=1000&amp;tags=',$node/@id))/nodes/node">
            <tr>
              <xsl:if test="not(@published)">
                <xsl:attribute name="class">hidden</xsl:attribute>
              </xsl:if>
              <td>
                <input type="checkbox" name="apply[]" value="{@id}" checked="checked" />
              </td>
              <td>
                <xsl:value-of select="@class" />
              </td>
              <td>
                <a href="admin/node/{@id}">
                  <xsl:value-of select="@name" />
                </a>
              </td>
            </tr>
          </xsl:for-each>
        </tbody>
      </table>
      <input type="submit" value="Сохранить" />
    </form>
  </xsl:template>
</xsl:stylesheet>

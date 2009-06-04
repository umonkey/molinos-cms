<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content" mode="content">
    <h2>
      <xsl:value-of select="@title" />
    </h2>
    <form method="post" action="nodeapi/delete?destination={$next}">
      <ol>
        <xsl:for-each select="node">
          <li>
            <input type="checkbox" name="selected[]" value="{@id}" checked="checked" />
            <a href="admin/node/{@id}">
              <xsl:value-of select="@name" />
            </a>
            <xsl:text> (</xsl:text>
            <xsl:value-of select="@type" />
            <xsl:text>)</xsl:text>
          </li>
        </xsl:for-each>
      </ol>
      <input type="submit" value="Подтверждаю" />
    </form>
  </xsl:template>
</xsl:stylesheet>

<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template match="widget[@class = 'TagCloudWidget']">
    <xsl:if test="count(tags/tag)">
      <ul class="tagcloud">
        <xsl:for-each select="tags/tag">
          <li>
            <xsl:if test="@percent">
              <xsl:attribute name="class">
                <xsl:text>size-</xsl:text>
                <xsl:value-of select="@percent" />
              </xsl:attribute>
            </xsl:if>
            <a href="{@link}">
              <xsl:value-of select="@name" />
            </a>
          </li>
        </xsl:for-each>
      </ul>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>

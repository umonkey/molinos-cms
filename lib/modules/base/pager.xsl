<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!-- листалка -->
  <xsl:template match="pager">
    <xsl:if test="@pages &gt; 1">
      <div class="pager">
        <xsl:if test="@prev">
          <a href="{@prev}">←</a>
        </xsl:if>
        <xsl:for-each select="page">
          <a>
            <xsl:if test="@link">
              <xsl:attribute name="href">
                <xsl:value-of select="@link" />
              </xsl:attribute>
              <xsl:attribute name="class">
                <xsl:text>current</xsl:text>
              </xsl:attribute>
            </xsl:if>
            <xsl:value-of select="@number" />
          </a>
        </xsl:for-each>
        <xsl:if test="@next">
          <a href="{@next}">→</a>
        </xsl:if>
      </div>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>

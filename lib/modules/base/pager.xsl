<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!-- листалка -->
  <xsl:template match="pager">
    <div class="pager">
      <xsl:if test="@prev">
        <a href="{@prev}">←</a>
      </xsl:if>
      <xsl:for-each select="page">
        <a href="{@link}">
          <xsl:if test="@link">
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
  </xsl:template>
</xsl:stylesheet>

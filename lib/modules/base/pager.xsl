<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!-- листалка -->
  <xsl:template match="pager">
    <xsl:if test="@pages &gt; 1">
      <ul class="pages">
        <xsl:if test="@prev">
          <li class="prev arrow"><a href="{@prev}">←</a></li>
        </xsl:if>
        <xsl:for-each select="page">
          <li>
          	<xsl:if test="not(@link)">
              <xsl:attribute name="class">
                <xsl:text>current</xsl:text>
              </xsl:attribute>
            </xsl:if>
          	<a>
              <xsl:if test="@link">
                <xsl:attribute name="href">
                  <xsl:value-of select="@link" />
                </xsl:attribute>
              </xsl:if>
              <xsl:value-of select="@number" />
		</a>
          </li>
        </xsl:for-each>
        <xsl:if test="@next">
          <li><a class="next arrow" href="{@next}">→</a></li>
        </xsl:if>
      </ul>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>

<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template match="widget[@class = 'SearchWidget']">
    <div id="{@name}-widget">
      <xsl:apply-templates select="search" mode="SearchWidget" />
    </div>
  </xsl:template>

  <xsl:template match="search[@mode = 'gas' and @onlyform]" mode="SearchWidget">
    <form method="get" action="{@resultpage}">
      <input class="form-text" type="text" name="{../@name}.query" />
      <input class="form-submit" type="submit" value="Найти" />
    </form>
  </xsl:template>

  <xsl:template match="search[@mode = 'gas' and not(@onlyform)]" mode="SearchWidget">
    <div id="gas">
      <div id="{@formctl}" />
      <div id="{@root}" />
    </div>
  </xsl:template>
</xsl:stylesheet>

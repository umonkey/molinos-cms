<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template match="page[@status='401']">
    <html lang="ru">
      <xsl:apply-templates select="." mode="head">
      </xsl:apply-templates>
    </html>
    <div id="login-form">
      <xsl:apply-templates select="form" />
    </div>
  </xsl:template>
</xsl:stylesheet>

<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="../../base/forms.xsl" />

  <xsl:template match="page[@status='401']">
    <html lang="ru">
      <link rel="stylesheet" href="{@prefix}/.admin.css" type="text/css" />
      <script type="text/javascript" src="{@prefix}/.admin.js" />
    </html>
    <body>
      <div id="login-form">
        <xsl:apply-templates select="form" />
      </div>
    </body>
  </xsl:template>
</xsl:stylesheet>

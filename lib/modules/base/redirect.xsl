<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:template name="redirect">
    <xsl:param name="href" />
    <xsl:param name="status" select="302" />

    <html>
      <head>
        <meta http-equiv="refresh" content="0;URL={$href}" />
      </head>
      <body>
        <h1>Перенаправление</h1>
        <p>Сейчас вы будете перенаправлены на другую страницу. Если этого не происходит, нажмите <a href="{$href}">сюда</a>.</p>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>

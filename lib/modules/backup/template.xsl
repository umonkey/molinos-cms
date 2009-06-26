<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../admin/template.xsl" />

  <xsl:template match="content" mode="content">
    <h2>Резервное копирование</h2>
    <p><a href="backup.zip">Скачать архив</a></p>
  </xsl:template>
</xsl:stylesheet>

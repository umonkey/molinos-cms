<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="/message[@mode='confirm']">
    <p>Привет!</p>
    <p>
      <xsl:text>Я — почтовый робот сайта </xsl:text>
      <xsl:value-of select="@host" />
      <xsl:text>, и я хотел бы уточнить, действительно ли вы хотите подписаться на новости нашего сайта</xsl:text>
      <xsl:if test="count(sections/section) = 1">
        <xsl:text> в разделе "</xsl:text>
        <xsl:value-of select="sections/section/@name" />
        <xsl:text>"?</xsl:text>
      </xsl:if>
    </p>
    <p>Для активации подписки воспользуйтесь <a href="{/message/@base}{/message/@confirmLink}">этой ссылкой</a>.</p>
    <p>Вы можете просто проигнорировать это сообщение, и ничего не произойдёт.</p>
  </xsl:template>

  <xsl:template match="/message[@mode='regular']">
    <xsl:value-of select="document/text" disable-output-escaping="yes" />
    <p>
      <a href="{/message/@unsubscribe}">Отписаться от этой рассылки</a>
    </p>
  </xsl:template>
</xsl:stylesheet>

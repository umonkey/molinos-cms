<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="/request">
    <p>Вы попросили напомнить ваш пароль для сайта <a href="{@base}"><xsl:value-of select="@host" /></a>. Восстановить старый пароль мы не можем, и менять его не стали, но вы можете войти, используя <a href="{@base}{@link}&amp;destination=%2F">вот эту</a> одноразовую ссылку.</p>
    <p>Вы можете проигнорировать это сообщение, и ваш пароль останется прежним.</p>
    <p>Спасибо за внимание.</p>
  </xsl:template>
</xsl:stylesheet>

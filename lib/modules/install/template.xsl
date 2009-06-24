<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../base/forms.xsl" />

  <xsl:output
    method="xml"
    encoding="utf-8"
    doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" 
    doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"
    indent="yes" />

  <xsl:template match="installer">
    <html>
      <head>
        <base href="{@base}" />
        <title>Установка Molinos CMS</title>
        <link rel="stylesheet" type="text/css" href="lib/modules/install/template.css" />
        <script type="text/javascript" src="lib/modules/admin/scripts/admin/00jquery.js" />
        <script type="text/javascript" src="lib/modules/install/template.js" />
      </head>
      <body>
        <h1>Установка Molinos CMS</h1>
        <form method="post" action="?q=admin/install">
          <xsl:apply-templates select="drivers" />
          <xsl:call-template name="settings" />
          <!--
          <xsl:call-template name="info" />
          -->
          <input id="submit" type="submit" value="Установить" />
        </form>
      </body>
    </html>
  </xsl:template>

  <xsl:template match="drivers">
    <fieldset id="drivers">
      <legend>Настройка базы данных</legend>
      <label>
        <span>Тип базы данных:</span>
        <select id="dbtype" name="dbtype">
          <option value="" checked="checked">(необходимо выбрать)</option>
          <xsl:apply-templates select="driver" />
        </select>
      </label>
      <xsl:apply-templates select="driver" mode="settings" />
    </fieldset>
  </xsl:template>

  <xsl:template match="driver">
    <option value="{@name}">
      <xsl:if test="@name = 'sqlite'">
        <xsl:attribute name="selected">
          <xsl:text>selected</xsl:text>
        </xsl:attribute>
      </xsl:if>
      <xsl:value-of select="@title" />
    </option>
  </xsl:template>

  <xsl:template match="driver[@name='sqlite']" mode="settings">
    <fieldset id="{@name}" class="driversettings">
      <legend>
        <span>Настройки </span>
        <xsl:value-of select="@title" />
      </legend>
      <label>
        <span>Путь к базе данных:</span>
        <xsl:call-template name="pathfield">
          <xsl:with-param name="name" select="'db[sqlite][name]'" />
          <xsl:with-param name="default" select="'database.sqlite'" />
        </xsl:call-template>
      </label>
    </fieldset>
  </xsl:template>

  <xsl:template match="driver[@name='mysql']" mode="settings">
    <fieldset id="{@name}" class="driversettings">
      <legend>
        <span>Настройки </span>
        <xsl:value-of select="@title" />
      </legend>
      <label class="dbhost">
        <span>Адрес сервера:</span>
        <input type="text" class="form-text" name="db[mysql][host]" value="localhost" />
      </label>
      <label class="dbname">
        <span>Имя базы данных:</span>
        <input type="text" class="form-text" name="db[mysql][name]" value="mcms" />
      </label>
      <label class="dbuser">
        <span>Имя пользователя:</span>
        <input type="text" class="form-text" name="db[mysql][name]" value="mcmsuser" />
      </label>
      <label class="dbpass">
        <span>Пароль:</span>
        <input type="password" class="form-text" name="db[mysql][pass]" value="mcmspass" />
      </label>
    </fieldset>
  </xsl:template>

  <xsl:template name="settings">
    <fieldset id="mail-settings">
      <legend>Настройки почты <span>(лучше поменять)</span></legend>
      <label>
        <span>Адрес почтового сервера:</span>
        <input type="text" class="form-text" name="modules/mail/server" value="localhost" />
      </label>
      <label>
        <span>Отправитель по умолчанию:</span>
        <input type="text" class="form-text" name="modules/mail/from" value="no-reply@example.com" />
      </label>
      <label>
        <span>Отправлять сообщения об ошибках на:</span>
        <input type="text" class="form-text" name="main/debug/errors" value="cms-bugs@molinos.ru" />
      </label>
    </fieldset>
    <fieldset>
      <legend>Параллельная установка <span>(лучше не трогать)</span></legend>
      <label>
        <span>Префикс для таблиц</span>
        <input type="text" name="db[prefix]" class="form-text" />
      </label>
    </fieldset>
  </xsl:template>

  <xsl:template name="info">
    <fieldset id="info">
      <legend>Дополнительная информация</legend>
      <ol>
        <li>При возникновении проблем обращайтесь к <a href="http://molinos-cms.googlecode.com/">разработчикам</a>.</li>
      </ol>
    </fieldset>
  </xsl:template>

  <xsl:template name="pathfield">
    <xsl:param name="name" />
    <xsl:param name="default" />
    <table>
      <tr>
        <th>
          <xsl:value-of select="/installer/@dirname" />
          <xsl:text>/</xsl:text>
        </th>
        <td>
          <input type="text" class="form-text" name="{$name}" value="{$default}" />
        </td>
      </tr>
    </table>
  </xsl:template>
</xsl:stylesheet>

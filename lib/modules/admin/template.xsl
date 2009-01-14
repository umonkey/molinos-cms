<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:output
    method="xml"
    encoding="utf-8"
    doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" 
    doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN"
    indent="no" />

  <xsl:template match="/page">
    <html>
      <head>
        <base href="{@base}"></base>
        <title>
          <xsl:text>Molinos CMS [v</xsl:text>
          <xsl:value-of select="signature/@version" />
          <xsl:text>]</xsl:text>
        </title>
        <link rel="stylesheet" type="text/css" href="themes/admin/css/bebop.css" />
        <link rel="stylesheet" type="text/css" href="themes/admin/css/style.css" />
        <link rel="stylesheet" type="text/css" href="themes/admin/css/notification.css" />
        <link rel="stylesheet" type="text/css" href="themes/admin/css/topmenu.css" />
        <link rel="stylesheet" type="text/css" href="themes/admin/css/colors-green.css" />
        <script type="text/javascript" src="themes/all/jquery/jquery.js"></script>
        <script type='text/javascript' src='themes/all/jquery/plugins/jquery.mcms.tabber.js'></script>
        <script type='text/javascript' src='themes/admin/js/bebop.js'></script>
        <script type='text/javascript'><![CDATA[var mcms_path = '';]]></script>
        <xsl:apply-templates select="extras/item" mode="extras" />
      </head>
      <body>
        <div id="preloaded_images"></div>

        <div id="all">
          <xsl:apply-templates select="menu" />
          <xsl:apply-templates select="toolbar" />

          <div id="content_wrapper">
            <div id="center">
              <xsl:apply-templates select="content/*" />
            </div>
          </div>
        </div>

        <xsl:apply-templates select="signature" />
      </body>
    </html>
  </xsl:template>


  <!-- навигационное меню -->
  <xsl:template match="menu">
    <div id="top_menu_controls">
      <ul>
        <xsl:apply-templates select="tab" mode="top_menu_controls" />
      </ul>
    </div>
  </xsl:template>

  <xsl:template match="tab" mode="top_menu_controls">
    <li class="{@class}">
      <a href="{@url}">
        <xsl:value-of select="@title" />
      </a>
      <ul>
        <xsl:apply-templates select="link" mode="top_menu_controls" />
      </ul>
    </li>
  </xsl:template>

  <xsl:template match="link" mode="top_menu_controls">
    <li>
      <a href="{@url}">
        <xsl:value-of select="@title" />
      </a>
    </li>
  </xsl:template>


  <!-- панель с иконками -->
  <xsl:template match="toolbar">
    <div id="navbar">
      <div id="top_toolbar">
        <div class="right">
          <xsl:apply-templates select="a" mode="mcms_toolbar" />
        </div>
      </div>
      <div id="top_menu_controls_bottom"></div>
    </div>
  </xsl:template>

  <xsl:template match="a" mode="mcms_toolbar">
    <xsl:if test="text()">
      <a href="{@href}" title="{@title}" class="{@class}">
        <xsl:value-of select="text()" />
      </a>
    </xsl:if>
    <xsl:if test="not(text())">
      <a href="{@href}" title="{@title}">
        <img src="themes/admin/img/icon-{@class}.png" alt="{@class}" width="16" height="16" />
      </a>
    </xsl:if>
  </xsl:template>


  <!-- информационные сообщения -->
  <xsl:template match="messages">
    <div id="desktop">
      <xsl:apply-templates select="group" mode="mcms_admin_messages" />
    </div>
  </xsl:template>

  <xsl:template match="group" mode="mcms_admin_messages">
    <fieldset>
      <legend>
        <xsl:value-of select="@title" />
      </legend>
      <ul>
        <xsl:apply-templates select="message" mode="mcms_admin_messages" />
      </ul>
    </fieldset>
  </xsl:template>

  <xsl:template match="message" mode="mcms_admin_messages">
    <li>
      <xsl:if test="@link">
        <a href="{@link}">
          <xsl:value-of select="text()" />
        </a>
      </xsl:if>
      <xsl:if test="not(@link)">
        <xsl:value-of select="text()" />
      </xsl:if>
    </li>
  </xsl:template>


  <!-- выбор типа создаваемого документа -->
  <xsl:template match="typechooser">
    <h2>Документ какого типа вы хотите создать?</h2>
    <dl>
      <xsl:for-each select="type">
        <dt>
          <a href="?q=admin/content/create&amp;type={@name}&amp;destination={../@destination}">
            <xsl:value-of select="@title" />
          </a>
        </dt>
        <xsl:if test="@description">
          <dd>
            <xsl:value-of select="@description" />
          </dd>
        </xsl:if>
      </xsl:for-each>
    </dl>
  </xsl:template>


  <!-- список документов -->
  <xsl:template match="list">
    <div class="doclist">
      <h2>
        <xsl:value-of select="@title" />
      </h2>
      <xsl:if test="not(count(data/node))">
        <p>
          <xsl:text>Нет данных</xsl:text>
          <xsl:if test="@type">
            <xsl:text>, </xsl:text>
            <a href="?q=admin/content/create&amp;type={@type}&amp;destination={/page/@urlEncoded}">добавить объект</a>?
          </xsl:if>
          <xsl:if test="not(@type)">
            <xsl:text>.</xsl:text>
          </xsl:if>
        </p>
      </xsl:if>
      <xsl:if test="count(data/node)">
        <xsl:if test="@search = 'yes'">
          <xsl:call-template name="mcms_list_search" />
        </xsl:if>
        <xsl:apply-templates select="massctl" mode="mcms_list" />

        <form id="nodeList" method="post" action="?q=nodeapi.rpc">
          <input id="nodeListCommand" type="hidden" name="action" value="" />
          <table class="mcms nodelist">
            <xsl:apply-templates select="data" mode="mcms_list" />
          </table>
        </form>
        <xsl:apply-templates select="massctl" mode="mcms_list" />
      </xsl:if>
    </div>
  </xsl:template>

  <!-- вывод пользователей -->
  <xsl:template match="data[../@preset = 'users']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="2"/>
        <th>Идентификатор</th>
        <th>Полное имя</th>
        <th>Email</th>
        <th>Дата регистрации</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-zoom" title="Найти все документы пользователя" href="?q=admin/content/list&amp;search=uid%3A{@id}">
              <span/>
            </a>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-fullname">
            <xsl:value-of select="@fullname" />
          </td>
          <td class="field-email">
            <xsl:value-of select="@email" />
          </td>
          <td class="field-created">
            <xsl:call-template name="FormatDate">
              <xsl:with-param name="timestamp" select="@created" />
            </xsl:call-template>
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <!-- вывод групп -->
  <xsl:template match="data[../@preset = 'groups']" mode="mcms_list">
    <thead>
      <tr>
        <th/>
        <th>Имя</th>
        <th>Описание</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-description">
            <xsl:value-of select="@description" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <!-- вывод разделов -->
  <xsl:template match="data[../@preset = 'taxonomy']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="5" />
        <th>Название</th>
        <th>Описание</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-add" title="Добавить подраздел" href="?q=admin&amp;cgroup={/page/@cgroup}&amp;mode=create&amp;type=tag&amp;parent={@id}&amp;destination={/page/@urlEncoded}">
              <span/>
            </a>
          </td>
          <td class="icon">
            <xsl:if test="position() != 1">
              <a class="icon-raise" title="Поднять раздел" href="?q=nodeapi.rpc&amp;action=raise&amp;node={@id}&amp;destination={/page/@urlEncoded}">
                <span/>
              </a>
            </xsl:if>
          </td>
          <td class="icon">
            <xsl:if test="position() != last()">
              <a class="icon-sink" title="Опустить раздел" href="?q=nodeapi.rpc&amp;action=sink&amp;node={@id}&amp;destination={/page/@urlEncoded}">
                <span/>
              </a>
            </xsl:if>
          </td>
          <td class="icon">
            <a class="icon-zoom" title="Найти все документы из этого раздела" href="?q=admin/content/list&amp;columns=name,class,uid,created&amp;search=tags%3A{@id}">
              <span/>
            </a>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-description">
            <xsl:value-of select="@description" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <!-- вывод типов документов -->
  <xsl:template match="data[../@preset = 'schema']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="2" />
        <th>Имя</th>
        <th>Имя отображаемое</th>
        <th>Описание</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-zoom" href="?q=admin/content/list&amp;search=class%3A{@name}" />
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-title">
            <xsl:value-of select="@title" />
          </td>
          <td class="field-description">
            <xsl:value-of select="@description" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <!-- вывод доменов -->
  <xsl:template match="data[../@preset = 'pages']" mode="mcms_list">
    <thead>
      <tr>
        <th/>
        <th>Имя</th>
        <th>Название</th>
        <th>Шкура</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-title">
            <xsl:value-of select="@title" />
          </td>
          <td class="field-theme">
            <xsl:value-of select="@theme" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <!-- вывод виджетов -->
  <xsl:template match="data[../@preset = 'widgets']" mode="mcms_list">
    <thead>
      <tr>
        <th/>
        <th>Имя</th>
        <th>Название</th>
        <th>Тип</th>
        <th>Описание</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-title">
            <xsl:value-of select="@title" />
          </td>
          <td class="field-classname">
            <xsl:value-of select="@classname" />
          </td>
          <td class="field-description">
            <xsl:value-of select="@description" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>


  <!-- вывод файлов -->
  <xsl:template match="data[../@preset = 'files']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="2"/>
        <th>Заголовок</th>
        <th>Имя файла</th>
        <th>Тип</th>
        <th class="field-filesize">Размер</th>
        <th/>
        <th>Владелец</th>
        <th>Дата добавления</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-download" href="{@_url}"></a>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-filename">
            <xsl:value-of select="@filename" />
          </td>
          <td class="field-filetype">
            <xsl:value-of select="@filetype" />
          </td>
          <td class="field-filesize">
            <xsl:value-of select="@filesize" />
          </td>
          <td>
            <xsl:if test="@width and @height">
              <xsl:value-of select="@width" />
              <xsl:text>x</xsl:text>
              <xsl:value-of select="@height" />
            </xsl:if>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_author" />
          <td class="field-created">
            <xsl:call-template name="FormatDate">
              <xsl:with-param name="timestamp" select="@created" />
            </xsl:call-template>
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>


  <!-- базовый вывод документов -->
  <xsl:template match="data" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="2"/>
        <th>Заголовок</th>
        <th>Тип</th>
        <th>Автор</th>
        <th>Дата добавления</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-locate" title="Найти документ на сайте" href="?q=nodeapi.rpc&amp;action=locate&amp;node={@id}">
              <span/>
            </a>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-class">
            <xsl:value-of select="@class" />
          </td>
          <xsl:apply-templates select="." mode="mcms_list_author" />
          <td class="field-created">
            <xsl:call-template name="FormatDate">
              <xsl:with-param name="timestamp" select="@created" />
            </xsl:call-template>
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <xsl:template match="node" mode="mcms_list_check">
    <td>
      <input type="checkbox" name="nodes[]" value="{@id}" />
    </td>
  </xsl:template>

  <xsl:template match="node" mode="mcms_list_name">
    <td class="field-name">
      <a href="?q=admin/{/page/@cgroup}/edit/{@id}&amp;destination={/page/@urlEncoded}">
        <xsl:if test="@depth">
          <xsl:attribute name="style">
            <xsl:text>padding-left:</xsl:text>
            <xsl:value-of select="@depth * 10" />
            <xsl:text>px</xsl:text>
          </xsl:attribute>
        </xsl:if>
        <xsl:value-of select="@displayName" />
      </a>
    </td>
  </xsl:template>

  <xsl:template match="node" mode="mcms_list_author">
    <td class="field-uid">
      <a href="?q=admin/access/edit/{@id}&amp;destination={/page/@urlEncoded}">
        <xsl:value-of select="@userName" />
      </a>
    </td>
  </xsl:template>

  <!-- Выбор нескольких строк таблицы. -->
  <xsl:template match="massctl" mode="mcms_list">
    <div class="tb_2">
      <div class="tb_2_inside">
        <div class="ctrl_left doc_selector">
          <span>Выбрать: </span>
          <xsl:apply-templates select="selector" mode="mcms_list_mass_controls" />
          <span class="and"> и </span>
          <xsl:apply-templates select="action" mode="mcms_list_mass_controls" />
          <xsl:text>.</xsl:text>
        </div>
        <div class="spacer_not_ie"></div>
      </div>
    </div>
  </xsl:template>

    <xsl:template match="selector" mode="mcms_list_mass_controls">
      <xsl:choose>
        <xsl:when test="position() = last()">
          <xsl:text> или </xsl:text>
        </xsl:when>
        <xsl:when test="position() != 1">
          <xsl:text>, </xsl:text>
        </xsl:when>
      </xsl:choose>
      <u class="fakelink select-{@name}">
        <xsl:value-of select="@title" />
      </u>
    </xsl:template>

    <xsl:template match="action" mode="mcms_list_mass_controls">
      <xsl:choose>
        <xsl:when test="position() = last()">
          <xsl:text> или </xsl:text>
        </xsl:when>
        <xsl:when test="position() != 1">
          <xsl:text>, </xsl:text>
        </xsl:when>
      </xsl:choose>
      <u class="fakelink actionlink action-{@name}">
        <xsl:value-of select="@title" />
      </u>
    </xsl:template>

  <!-- Форма поиска. -->
  <xsl:template name="mcms_list_search" mode="mcms_list">
    <form method="post" action="?q=admin.rpc&amp;action=search">
      <input type="hidden" name="search_from" value="{/page/@url}" />
      <div class="tb_1">
        <div class="ctrl_left">
          <a class="newlink" href="?q=admin/{/page/@cgroup}/create&amp;type={@type}&amp;destination={/page/@urlEncoded}">Добавить</a>
          <xsl:text> | </xsl:text>
          <input type="text" name="search_term" class="search_field" value="" />
          <input type="submit" value="Найти" />
          <xsl:text> | </xsl:text>
          <a href="?q=admin/content/search&amp;from={/page/@urlEncoded}">Расширенный поиск</a>
        </div>
      </div>
    </form>
  </xsl:template>

  <xsl:template name="odd_row">
    <xsl:attribute name="class">
      <xsl:if test="position() mod 2">
        <xsl:text> odd</xsl:text>
      </xsl:if>
      <xsl:if test="@published">
        <xsl:text> published</xsl:text>
      </xsl:if>
    </xsl:attribute>
    <td class="selector">
      <input type="checkbox" name="nodes[]" value="{@id}" />
    </td>
  </xsl:template>

  <!-- Подпись в подвале. -->
  <xsl:template match="signature">
    <div id="footer" class="signature">
      <hr/>
      <em>
        <xsl:text>Molinos CMS </xsl:text>
        <a href="{@version_link}">
          <xsl:text>v</xsl:text>
          <xsl:value-of select="@version" />
        </a>
        <xsl:if test="@options">
          <xsl:text> [</xsl:text>
          <xsl:value-of select="@options" />
          <xsl:text>]</xsl:text>
        </xsl:if>
        <xsl:text> at </xsl:text>
        <a href=".">
          <xsl:value-of select="@at" />
        </a>
        <xsl:text>, client IP: </xsl:text>
        <xsl:value-of select="@client" />
        <xsl:text>.</xsl:text>
      </em>
      <xsl:value-of select="text()" disable-output-escaping="yes" />
    </div>
  </xsl:template>


  <!-- вывод дополнительных стилей и скриптов -->
  <xsl:template match="item[@type = 'style']" mode="extras">
    <link rel="stylesheet" type="text/css" href="{@value}" />
  </xsl:template>
  <xsl:template match="item[@type = 'script']" mode="extras">
    <script type="text/javascript" src="{@value}"></script>
  </xsl:template>


  <!-- форматирование даты -->
  <xsl:template name="FormatDate">
    <xsl:param name="timestamp" />
    <xsl:value-of select="substring($timestamp,9,2)" />
    <xsl:text>.</xsl:text>
    <xsl:value-of select="substring($timestamp,6,2)" />
    <xsl:text>.</xsl:text>
    <xsl:value-of select="substring($timestamp,3,2)" />
    <xsl:text>, </xsl:text>
    <xsl:value-of select="substring($timestamp,12,5)" />
  </xsl:template>

  <!-- Шаблоны контролов -->
  <xsl:include href="../base/forms.xsl" />
</xsl:stylesheet>

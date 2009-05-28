<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
  <xsl:import href="../base/forms2.xsl" />
  <xsl:import href="../base/pager.xsl" />
  <xsl:import href="../base/redirect.xsl" />
  <xsl:import href="xsl/submenu.xsl" />
  <xsl:import href="xsl/dashboard.xsl" />

  <xsl:variable name="api" select="'cms://localhost/api/'" />
  <xsl:variable name="search" select="/page/request/getArgs/arg[@name='search']/text()" />
  <xsl:variable name="query" select="/page/@query" />
  <xsl:variable name="back" select="/page/@back" />
  <xsl:variable name="sendto" select="/page/request/getArgs/arg[@name='sendto']/text()" />
  <xsl:variable name="next" select="/page/request/getArgs/arg[@name='destination']/text()" />
  <xsl:variable name="page" select="/page/request/getArgs/arg[@name='page']/text()" />

  <xsl:output omit-xml-declaration="yes" method="xml" version="1.0" encoding="UTF-8" indent="yes" doctype-public="-//W3C//DTD XHTML 1.0 Strict//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"/>

  <!-- стандартный заголовок для всех страниц админки -->
  <xsl:template match="page" mode="head">
    <xsl:param name="title" select="'Molinos CMS'" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>
      <xsl:value-of select="$title" />
    </title>
    <xsl:comment><![CDATA[[if IE]><![if !IE]><![endif]]]></xsl:comment><base href="{@base}" /><xsl:comment><![CDATA[[if IE]><![endif]><![endif]]]></xsl:comment>
    <xsl:comment><![CDATA[[if IE]>]]>&lt;base href="<xsl:value-of select="@base"/>">&lt;/base><![CDATA[<![endif]]]></xsl:comment>
    <link rel="shortcut icon" href="lib/modules/admin/styles/admin/images/icons/favicon.ico" type="image/x-icon" />
    <link rel="stylesheet" href="{@prefix}/.admin.css" type="text/css" />
    <script type="text/javascript" src="{@prefix}/.admin.js" />
  </xsl:template>

  <xsl:template match="/page[@status=401]">
    <xsl:call-template name="redirect">
      <xsl:with-param name="href">
        <xsl:value-of select="@base" />
        <xsl:text>?q=admin/login&amp;destination=</xsl:text>
        <xsl:value-of select="@back" />
      </xsl:with-param>
    </xsl:call-template>
  </xsl:template>

  <xsl:template match="/page[@status=200]">
    <html lang="ru">
      <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>
          <xsl:if test="content[@title]">
            <xsl:value-of select="content[position() = 1]/@title" />
            <xsl:text> — </xsl:text>
          </xsl:if>
          <xsl:text>Molinos CMS v</xsl:text>
          <xsl:value-of select="@version" />
        </title>
        <xsl:comment><![CDATA[[if IE]><![if !IE]><![endif]]]></xsl:comment><base href="{/page/@base}" /><xsl:comment><![CDATA[[if IE]><![endif]><![endif]]]></xsl:comment>
        <xsl:comment><![CDATA[[if IE]>]]>&lt;base href="<xsl:value-of select="/page/@base"/>">&lt;/base><![CDATA[<![endif]]]></xsl:comment>
        <link rel="shortcut icon" href="lib/modules/admin/styles/admin/images/icons/favicon.ico" type="image/x-icon" />
        <link rel="stylesheet" href="{@prefix}/.admin.css" type="text/css" />
        <script type="text/javascript" src="{@prefix}/.admin.js" />
      </head>
      <body>
        <xsl:apply-templates select="." mode="body" />
      </body>
    </html>
  </xsl:template>

  <xsl:template match="/page[@status!=200 and @status!=401]">
    <html>
      <xsl:apply-templates select="." mode="head" />
      <body>
        <h1>Ошибка <xsl:value-of select="@status" /></h1>
        <p><xsl:value-of select="@message" disable-output-escaping="yes" /></p>
      </body>
    </html>
  </xsl:template>

  <xsl:template match="page" mode="body">

    <div id="wrapper">

      <div id="non-footer">

        <div id="toolbar">
          <xsl:apply-templates select="menu" />
          <xsl:apply-templates select="request/user/node" mode="toolbar" />
        </div>

        <div id="content">
          <xsl:apply-templates select="content" mode="content" />
          <xsl:apply-templates select="content/pager" />
        </div>

      </div>

      <div id="footer">
        <a href="http://molinos-cms.googlecode.com/">Molinos CMS</a>
              <xsl:text> v</xsl:text>
              <xsl:value-of select="/page/@version" />
              <xsl:text> [</xsl:text>
              <xsl:value-of select="/page/@memory" />
              <xsl:text>+</xsl:text>
              <xsl:value-of select="/page/@cache" />
              <xsl:text>] at </xsl:text>
              <a href="{@base}">
                <xsl:value-of select="@host" />
              </a>
      </div>

    </div>

  </xsl:template>

  <!-- всякие ошибки -->
  <xsl:template match="page[@status != 200]" mode="body">
    <div id="exception">
      <h2>Ошибка <xsl:value-of select="@status" /></h2>
      <p>
        <xsl:value-of select="@title" disable-output-escaping="yes" />
      </p>
    </div>
  </xsl:template>


  <!-- произвольная форма -->
  <xsl:template match="content[@name = 'form']" mode="content">
    <xsl:apply-templates select="form" />
  </xsl:template>

  <!-- навигационное меню -->
  <xsl:template match="menu">
    <h1><a href="?q=admin"><img src="lib/modules/admin/styles/admin/images/logos/cms-tp.png" alt="Molinos CMS" /></a></h1>
      <ul class="navigation">
        <xsl:for-each select="path">
            <li>
              <xsl:if test="@name = /page/location/@tab">
                <xsl:attribute name="class">
                  <xsl:text>current</xsl:text>
                </xsl:attribute>
              </xsl:if>
      <xsl:if test="path">
        <xsl:attribute name="class">
                <xsl:text> group</xsl:text>
              </xsl:attribute>
      </xsl:if>
              <a href="{@name}">
                <span><xsl:value-of select="@title" /></span>
              </a>
              <xsl:if test="path">
                <ul>
                  <xsl:for-each select="path">
                    <xsl:sort select="@sort" />
                    <xsl:sort select="@title" />
                    <li>
                      <a href="{@name}">
                        <xsl:value-of select="@title" />
                      </a>
                    </li>
                  </xsl:for-each>
                </ul>
              </xsl:if>
            </li>
        </xsl:for-each>
        <xsl:apply-templates select="tab" mode="top_menu_controls" />
      </ul>
  </xsl:template>

  <xsl:template match="link" mode="top_menu_controls">
    <li>
      <a href="{@url}">
        <xsl:value-of select="@title" />
      </a>
    </li>
  </xsl:template>


  <!-- панель с иконками -->
  <xsl:template match="node" mode="toolbar">
    <ul class="utilitary">
      <li>
        <a class="editprofile" href="admin/node/{@id}">
          <xsl:apply-templates select="." mode="username" />
        </a>
      </li>
      <!--
      <li>
        <a title="Вернуться на главную" href="?q=admin">
          <img src="lib/modules/admin/styles/admin/images/icons/icon-home.png" alt="home" width="16" height="16" />
        </a>
      </li>
      -->
      <li>
        <a title="Выйти" href="?q=auth/logout&amp;from={/page/@back}">
          <img src="lib/modules/admin/styles/admin/images/icons/icon-exit.png" alt="logout" width="16" height="16" />
        </a>
      </li>
    </ul>
  </xsl:template>

  <xsl:template match="a" mode="mcms_toolbar">
    <xsl:if test="text()">
      <a href="{@href}" title="{@title}" class="{@class}">
        <xsl:value-of select="text()" />
      </a>
    </xsl:if>
    <xsl:if test="not(text())">
      <a href="{@href}" title="{@title}">
        <img src="lib/modules/admin/styles/admin/images/icons/icon-{@class}.png" alt="{@class}" width="16" height="16" />
      </a>
    </xsl:if>
  </xsl:template>

  <!-- форма редактирования -->
  <xsl:template match="content[@name = 'edit' or @name = 'create']" mode="content">
    <xsl:apply-templates select="form|typechooser" />
  </xsl:template>

  <!-- информационные сообщения -->
  <xsl:template match="content[@name = 'messages']" mode="content">
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


  <!-- расширенный поиск -->
  <xsl:template match="content[@name = 'search']" mode="content">
    <h2>Расширенный поиск</h2>
    <form method="post" class="advsearch" action="?q=admin/search&amp;from={@from}">
      <xsl:if test="count(types/type) = 1">
        <input type="hidden" name="search_class" value="{types/type/@name}" />
      </xsl:if>
      <table>
        <tr>
          <th>
            <xsl:text>Запрос:</xsl:text>
          </th>
          <td>
            <input type="text" class="form-text" name="search_term" value="{@query}" />
          </td>
        </tr>
        <xsl:if test="count(types/type) &gt; 1">
          <tr>
            <th>
              <xsl:text>Тип документа:</xsl:text>
            </th>
            <td>
              <select name="search_class">
                <option value="">(любой)</option>
                <xsl:for-each select="types/type">
                  <option value="{@name}">
                    <xsl:value-of select="@title" />
                  </option>
                </xsl:for-each>
              </select>
            </td>
          </tr>
        </xsl:if>
        <xsl:if test="count(users/user)">
          <tr>
            <th>
              <xsl:text>Автор:</xsl:text>
            </th>
            <td>
              <select name="search_uid">
                <option value="">(любой)</option>
                <xsl:for-each select="users/user">
                  <option value="{@id}">
                    <xsl:value-of select="@name" />
                  </option>
                </xsl:for-each>
              </select>
            </td>
          </tr>
        </xsl:if>
        <xsl:if test="count(sections/section)">
          <tr>
            <th>
              <xsl:text>Раздел:</xsl:text>
            </th>
            <td>
              <select name="search_tag">
                <option value="">(любой)</option>
                <xsl:for-each select="sections/section">
                  <option value="{@id}">
                    <xsl:value-of select="@name" disable-output-escaping="yes" />
                  </option>
                </xsl:for-each>
              </select>
            </td>
          </tr>
          <tr>
            <th/>
            <td>
              <label>
                <input type="checkbox" name="search_recurse_tags" value="1" checked="checked" />
                <xsl:text>включая подразделы</xsl:text>
              </label>
            </td>
          </tr>
        </xsl:if>
        <tr class="submit">
          <th/>
          <td>
            <input type="submit" value="Найти" />
          </td>
        </tr>
      </table>
    </form>
  </xsl:template>


  <!-- выбор типа создаваемого документа -->
  <xsl:template match="typechooser">
    <h2>Документ какого типа вы хотите создать?</h2>
    <dl>
      <xsl:for-each select="type[not(isdictionary)]">
        <xsl:sort select="title" />
        <dt>
          <a href="?q=admin/create/{@name}?destination={../@destination}">
            <xsl:value-of select="title" />
          </a>
        </dt>
        <xsl:if test="description">
          <dd>
            <xsl:value-of select="description" />
          </dd>
        </xsl:if>
      </xsl:for-each>
    </dl>
  </xsl:template>

  <!-- вывод дополнительных стилей и скриптов -->
  <xsl:template match="item[@type = 'style']" mode="extras">
    <link rel="stylesheet" type="text/css" href="{@value}" />
  </xsl:template>
  <xsl:template match="item[@type = 'script']" mode="extras">
    <script type="text/javascript" src="{@value}"></script>
  </xsl:template>

  <!-- расширенный файловый контрол -->
  <xsl:template match="control[@type = 'attachment' and not(@newfile)]">
    <div id="file-{@id}">
      <xsl:call-template name="default_control_classes" />
      <xsl:call-template name="default_control_label" />
      <input type="hidden" name="{@name}[id]" id="file-{@id}-replace" value="{@id}" />

      <table>
        <tr>
          <th>
            <span class="preview" />
          </th>
          <td>
            <input type="text" class="info form-text active" name="{@name}[filename]" value="{@filename}" />
            <input type="file" class="form-file" name="{@name}" />
            <input type="text" class="url form-text" name="{@name}[url]" />
            <span class="delete">файл будет удалён</span>
            <span class="replace">файл будет заменён</span>
          </td>
          <th>
            <span class="switch-info" title="Показать информацию о файле" />
          </th>
          <th>
            <span class="switch-url" title="Загрузить с другого сайта" />
          </th>
          <th>
            <span class="switch-file" title="Загрузить со своего компьютера" />
          </th>
          <th>
            <span class="switch-find" title="Подобрать в файловом архиве" />
          </th>
          <th class="delete">
            <span class="delete" title="Убрать отсюда этот файл" />
          </th>
        </tr>
      </table>

      <xsl:call-template name="default_control_info" />
    </div>
  </xsl:template>

  <xsl:template match="@created|@updated">
    <xsl:if test=".">
      <xsl:value-of select="substring(.,9,2)" />
      <xsl:text>.</xsl:text>
      <xsl:value-of select="substring(.,6,2)" />
      <xsl:text>.</xsl:text>
      <xsl:value-of select="substring(.,3,2)" />
      <xsl:text>, </xsl:text>
      <xsl:value-of select="substring(.,12,5)" />
    </xsl:if>
  </xsl:template>

  <xsl:template match="*" mode="username">
    <xsl:choose>
      <xsl:when test="fullname">
        <xsl:value-of select="fullname" />
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="@name" />
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>

  <xsl:template name="filesize">
    <xsl:param name="size" select="0" />
    <xsl:choose>
      <xsl:when test="$size &gt; 1073741824">
        <xsl:value-of select="round($size div 1073741824)" />
        <xsl:text>Г</xsl:text>
      </xsl:when>
      <xsl:when test="$size &gt;= 1048576">
        <xsl:value-of select="round($size div 1048576)" />
        <xsl:text>М</xsl:text>
      </xsl:when>
      <xsl:when test="$size &gt; 1024">
        <xsl:value-of select="round($size div 1024)" />
        <xsl:text>К</xsl:text>
      </xsl:when>
      <xsl:otherwise>
        <xsl:value-of select="$size" />
        <xsl:text>Б</xsl:text>
      </xsl:otherwise>
    </xsl:choose>
  </xsl:template>
</xsl:stylesheet>

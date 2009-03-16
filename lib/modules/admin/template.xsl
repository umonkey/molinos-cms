<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../base/forms.xsl" />
  <xsl:import href="../base/pager.xsl" />
  <xsl:import href="list.xsl" />

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
          <xsl:if test="blocks/block[@title]">
            <xsl:value-of select="blocks/block[position() = 1]/@title" />
            <xsl:text> — </xsl:text>
          </xsl:if>
          <xsl:text>Molinos CMS v</xsl:text>
          <xsl:value-of select="@version" />
        </title>
        <link rel="stylesheet" type="text/css" href="lib/modules/admin/template.css" />
        <script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.2.6/jquery.min.js"></script>
        <script type='text/javascript' src='lib/modules/admin/js/bebop.js'></script>
        <script type='text/javascript'>
          <xsl:text>var mcms_path = '</xsl:text>
          <xsl:value-of select="/page/@folder" />
          <xsl:text>';</xsl:text>
        </script>
        <xsl:apply-templates select="extras/item" mode="extras" />
      </head>
      <body>
        <xsl:apply-templates select="." mode="body" />
      </body>
    </html>
  </xsl:template>

  <xsl:template match="page" mode="body">
    <div id="preloaded_images"></div>

    <div id="all">
      <xsl:apply-templates select="blocks/block[@name = 'menu']" />
      <xsl:apply-templates select="request/user" mode="toolbar" />

      <div id="content_wrapper">
        <div id="center">
          <xsl:apply-templates select="blocks/block" mode="content" />
          <xsl:apply-templates select="blocks/block/pager" />
        </div>
      </div>

      <div id="signature">
        <hr/>
        <a href="http://molinos-cms.googlecode.com/">Molinos CMS</a>
        <xsl:text> v</xsl:text>
        <xsl:value-of select="/page/@version" />
        <xsl:text> at </xsl:text>
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
        <xsl:value-of select="@title" />
      </p>
    </div>
  </xsl:template>


  <!-- авторизация -->
  <xsl:template match="page[@status = '401']" mode="body">
    <div id="login-form">
      <form method="post" action="?q=user.rpc&amp;action=login&amp;destination={/page/@back}" enctype="multipart/form-data">
        <fieldset>
          <legend><span>Требуется авторизация</span></legend>
          <div class="control">
            <label>
              <span>Email:</span>
              <input class="text login" type="text" name="login" />
            </label>
          </div>
          <div class="control">
            <label>
              <span>Пароль:</span>
              <input class="text password" type="password" name="password" />
            </label>
          </div>
          <div class="control">
            <label>
              <input type="checkbox" name="remember" value="1" checked="checked" />
              <xsl:text>Помнить меня 2 недели</xsl:text>
            </label>
          </div>
          <div class="submit-wrapper">
            <input class="submit submit1" type="submit" value="Войти" />
          </div>
        </fieldset>
      </form>
    </div>
  </xsl:template>


  <!-- произвольная форма -->
  <xsl:template match="block[@name = 'form']" mode="content">
    <xsl:apply-templates select="form" />
  </xsl:template>


  <!-- навигационное меню -->
  <xsl:template match="block[@name = 'menu']">
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
  <xsl:template match="user" mode="toolbar">
    <div id="navbar">
      <div id="top_toolbar">
        <div class="right">
          <a class="editprofile" href="?q=admin&amp;cgroup=access&amp;action=edit&amp;node={@id}&amp;destination={/page/request/@uri}">
            <xsl:apply-templates select="." mode="username" />
          </a>
          <a title="Вернуться на главную" href="?q=admin">
            <img src="lib/modules/admin/img/icons/icon-home.png" alt="home" width="16" height="16" />
          </a>
          <a title="Очистить кэш" href="?q=admin.rpc&amp;action=reload&amp;destination={/page/request/@uri}">
            <img src="lib/modules/admin/img/icons/icon-reload.png" alt="reload" width="16" height="16" />
          </a>
          <a title="Выйти" href="?q=user.rpc&amp;action=logout&amp;from={/page/request/@uri}">
            <img src="lib/modules/admin/img/icons/icon-exit.png" alt="logout" width="16" height="16" />
          </a>
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
        <img src="lib/modules/admin/img/icons/icon-{@class}.png" alt="{@class}" width="16" height="16" />
      </a>
    </xsl:if>
  </xsl:template>


  <!-- форма редактирования -->
  <xsl:template match="block[@name = 'edit' or @name = 'create']" mode="content">
    <xsl:apply-templates select="form|typechooser" />
  </xsl:template>


  <!-- рабочий стол -->
  <xsl:template match="block[@name='dashboard']" mode="content">
    <div id="dashboard">
      <form class="tabbed">
        <xsl:for-each select="block" mode="dashboard">
          <fieldset id="dashboard-{@name}" class="tabable">
            <legend class="toggle">
              <span class="title">
                <xsl:value-of select="@title" />
              </span>
              <span class="more">
                <xsl:if test="@more">
                  <xsl:text>(</xsl:text>
                  <a href="{@more}">ещё</a>
                  <xsl:text>)</xsl:text>
                </xsl:if>
              </span>
            </legend>
            <div>
              <xsl:apply-templates select="." mode="dashboard" />
            </div>
          </fieldset>
        </xsl:for-each>
      </form>
    </div>
  </xsl:template>
    <xsl:template match="block[@name='create']" mode="dashboard">
      <xsl:for-each select="node[not(isdictionary)]">
        <xsl:sort select="title" />
        <a class="create-{@name}" href="?q=admin&amp;action=create&amp;cgroup=content&amp;type={@name}&amp;destination={/page/request/@uri}">
          <xsl:if test="description">
            <xsl:attribute name="title">
              <xsl:value-of select="description" />
            </xsl:attribute>
          </xsl:if>
          <span>
            <xsl:value-of select="title" />
          </span>
        </a>
      </xsl:for-each>
    </xsl:template>
    <xsl:template match="block[@name='status']" mode="dashboard">
      <xsl:if test="message">
        <ol>
          <xsl:for-each select="message">
            <a href="{@link}">
              <xsl:value-of select="text()" />
            </a>
          </xsl:for-each>
        </ol>
      </xsl:if>
    </xsl:template>
    <xsl:template match="block" mode="dashboard">
      <ol class="doclist">
        <xsl:for-each select="node">
          <li>
            <a href="?q=admin.rpc&amp;action=edit&amp;cgroup=content&amp;node={@id}&amp;destination={/page/request/@uri}">
              <xsl:value-of select="@name" />
            </a>
          </li>
        </xsl:for-each>
      </ol>
      <!--
      <p>
        <a href="{@more}">
          <xsl:text>Полный список</xsl:text>
        </a>
      </p>
      -->
    </xsl:template>

  <!-- информационные сообщения -->
  <xsl:template match="block[@name = 'messages']" mode="content">
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
  <xsl:template match="block[@name = 'search']" mode="content">
    <h2>Расширенный поиск</h2>
    <form method="post" class="advsearch" action="?q=admin.rpc&amp;action=search&amp;from={@from}">
      <table>
        <tr>
          <th>
            <xsl:text>Запрос:</xsl:text>
          </th>
          <td>
            <input type="text" class="form-text" name="search_term" value="{@query}" />
          </td>
        </tr>
        <xsl:if test="count(types/type)">
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
                  <option value="{@name}">
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
      <xsl:for-each select="type">
        <xsl:sort select="title" />
        <dt>
          <a href="?q=admin.rpc&amp;action=create&amp;cgroup={/page/@cgroup}&amp;type={@name}&amp;destination={../@destination}">
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


  <!-- Подпись в подвале. -->
  <xsl:template match="block[@name = 'signature']">
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

  <xsl:template match="@created">
    <xsl:if test="text()">
      <xsl:value-of select="substring(text(),9,2)" />
      <xsl:text>.</xsl:text>
      <xsl:value-of select="substring(text(),6,2)" />
      <xsl:text>.</xsl:text>
      <xsl:value-of select="substring(text(),3,2)" />
      <xsl:text>, </xsl:text>
      <xsl:value-of select="substring(text(),12,5)" />
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
</xsl:stylesheet>

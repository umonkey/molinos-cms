<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!-- список документов -->
  <xsl:template match="block[@name = 'list' or @name = 'tree']" mode="content">
    <div class="doclist">
      <h2>
        <xsl:value-of select="@title" />
      </h2>
      <xsl:if test="not(count(data/node))">
        <xsl:if test="@search = 'yes'">
          <xsl:call-template name="mcms_list_search" />
        </xsl:if>
        <p>
          <xsl:text>Нет данных для отображения. Пересмотрите поисковый запрос или добавьте новый объект.</xsl:text>
        </p>
      </xsl:if>
      <xsl:if test="count(data/node)">
        <xsl:if test="@search = 'yes'">
          <xsl:call-template name="mcms_list_search" />
        </xsl:if>
        <xsl:apply-templates select="massctl" mode="mcms_list" />

        <form id="nodeList" method="post" action="?q=nodeapi.rpc&amp;destination={/page/request/@uri}">
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
        <th colspan="3"/>
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
            <a class="icon-zoom" title="Найти все документы пользователя" href="?q=admin.rpc&amp;action=list&amp;cgroup=content&amp;search=uid%3A{@id}">
              <span/>
            </a>
          </td>
          <td class="icon">
            <xsl:if test="@id != /page/request/user/@id">
              <a class="icon-sudo" title="Переключиться в пользователя" href="?q=user.rpc&amp;action=su&amp;uid={@id}&amp;destination={/page/request/@uri}">
                <span/>
              </a>
            </xsl:if>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-fullname">
            <xsl:value-of select="fullname" />
          </td>
          <td class="field-email">
            <xsl:value-of select="email" />
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
        <th colspan="2"/>
        <th>Имя</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-zoom" title="Найти всех пользователей группы" href="?q=admin.rpc&amp;action=list&amp;cgroup=access&amp;preset=users&amp;search=tags%3A{@id}">
              <span/>
            </a>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <!-- список полей -->
  <xsl:template match="data[../@type='field']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="1" />
        <th>№</th>
        <th>Имя</th>
        <th>Название</th>
        <th>Индекс</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <xsl:sort select="weight" data-type="number" />
        <xsl:sort select="@name" data-type="text" />
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="r">
            <xsl:value-of select="weight" />
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td>
            <xsl:value-of select="label" />
          </td>
          <td>
            <xsl:if test="indexed">
              <xsl:text>X</xsl:text>
            </xsl:if>
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
      </tr>
    </thead>
    <tbody>
      <xsl:apply-templates select="node" mode="mcms_taxonomy_tree">
        <xsl:with-param name="depth" select="0" />
      </xsl:apply-templates>
    </tbody>
  </xsl:template>

  <xsl:template match="node" mode="mcms_taxonomy_tree">
    <xsl:param name="depth" />
    <tr>
      <xsl:call-template name="odd_row" />
      <td class="icon">
        <a class="icon-add" title="Добавить подраздел" href="?q=admin.rpc&amp;action=create&amp;type=tag&amp;parent={@id}&amp;cgroup={/page/@cgroup}&amp;destination={/page/request/@uri}">
          <span/>
        </a>
      </td>
      <td class="icon">
        <xsl:if test="position() != 1">
          <a class="icon-raise" title="Поднять раздел" href="?q=nodeapi.rpc&amp;action=raise&amp;node={@id}&amp;destination={/page/request/@uri}">
            <span/>
          </a>
        </xsl:if>
      </td>
      <td class="icon">
        <xsl:if test="position() != last()">
          <a class="icon-sink" title="Опустить раздел" href="?q=nodeapi.rpc&amp;action=sink&amp;node={@id}&amp;destination={/page/request/@uri}">
            <span/>
          </a>
        </xsl:if>
      </td>
      <td class="icon">
        <a class="icon-zoom" title="Найти все документы из этого раздела" href="?q=admin.rpc&amp;action=list&amp;cgroup=content&amp;columns=name,class,uid,created&amp;search=tags%3A{@id}">
          <span/>
        </a>
      </td>
      <xsl:apply-templates select="." mode="mcms_list_name">
        <xsl:with-param name="depth" select="$depth" />
      </xsl:apply-templates>
    </tr>
    <xsl:apply-templates select="children/node" mode="mcms_taxonomy_tree">
      <xsl:with-param name="depth" select="$depth + 1" />
    </xsl:apply-templates>
  </xsl:template>


  <!-- вывод типов документов -->
  <xsl:template match="data[../@preset='schema']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="2" />
        <th>Имя</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <xsl:sort select="@published" order="descending" />
        <xsl:sort select="title" />
        <xsl:if test="not(isdictionary)">
          <tr>
            <xsl:call-template name="odd_row" />
            <td class="icon">
              <a class="icon-zoom" href="?q=admin.rpc&amp;action=list&amp;cgroup=content&amp;type={@name}" />
            </td>
            <td>
              <a href="?q=admin.rpc&amp;cgroup=structure&amp;action=edit&amp;node={@id}&amp;destination={/page/request/@uri}">
                <xsl:value-of select="title" />
              </a>
            </td>
          </tr>
        </xsl:if>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <!-- вывод справочников -->
  <xsl:template match="data[../@preset='dictlist']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="2" />
        <th>Имя</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <xsl:sort select="title" />
        <xsl:if test="isdictionary and @name != 'field'">
          <tr>
            <xsl:call-template name="odd_row" />
            <td class="icon">
              <a class="icon-zoom" href="?q=admin.rpc&amp;action=list&amp;cgroup=content&amp;type={@name}" />
            </td>
            <td>
              <a href="?q=admin.rpc&amp;cgroup=content&amp;action=edit&amp;node={@id}&amp;destination={/page/request/@uri}">
                <xsl:value-of select="title" />
              </a>
            </td>
          </tr>
        </xsl:if>
      </xsl:for-each>
    </tbody>
  </xsl:template>


  <!-- вывод доменов -->
  <xsl:template match="data[../@preset = 'pages']" mode="mcms_list">
    <xsl:variable name="domains" select="not(node/@parent_id)" />
    <thead>
      <tr>
        <th colspan="2" />
        <th>Имя</th>
        <th>Название</th>
        <th>Шкура</th>
      </tr>
    </thead>
    <tbody>
      <xsl:apply-templates select="node" mode="node_pages_tree">
        <xsl:with-param name="domains" select="$domains" />
        <xsl:with-param name="depth" select="0" />
      </xsl:apply-templates>
    </tbody>
  </xsl:template>

  <xsl:template match="node" mode="node_pages_tree">
    <xsl:param name="domains" />
    <xsl:param name="depth" />
    <tr>
      <xsl:call-template name="odd_row" />
      <xsl:choose>
        <xsl:when test="$domains">
          <td class="icon">
            <a class="icon-edit" href="?q=admin.rpc&amp;action=edit&amp;cgroup=structure&amp;node={@id}&amp;destination={/page/request/@uri}" />
          </td>
          <td class="field-name">
            <a href="?q=admin.rpc&amp;action=tree&amp;preset=pages&amp;subid={@id}&amp;cgroup={/page/@cgroup}">
              <xsl:if test="$depth">
                <xsl:attribute name="style">
                  <xsl:text>padding-left:</xsl:text>
                  <xsl:value-of select="$depth * 10" />
                  <xsl:text>px</xsl:text>
                </xsl:attribute>
              </xsl:if>
              <xsl:value-of select="@name" />
            </a>
          </td>
        </xsl:when>
        <xsl:otherwise>
          <td class="icon">
            <a class="icon-add" href="?q=admin.rpc&amp;action=create&amp;type=domain&amp;parent={@id}&amp;destination={/page/request/@uri}" />
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name">
            <xsl:with-param name="depth" select="$depth" />
          </xsl:apply-templates>
        </xsl:otherwise>
      </xsl:choose>
      <td class="field-title">
        <xsl:value-of select="title" />
      </td>
      <td class="field-theme">
        <xsl:value-of select="theme" />
      </td>
    </tr>
    <xsl:apply-templates select="children/node" mode="node_pages_tree">
      <xsl:with-param name="domains" select="$domains" />
      <xsl:with-param name="depth" select="$depth + 1" />
    </xsl:apply-templates>
  </xsl:template>

  <!-- вывод виджетов -->
  <xsl:template match="data[../@preset = 'widgets']" mode="mcms_list">
    <thead>
      <tr>
        <th/>
        <th>Имя</th>
        <th>Название</th>
        <th>Тип</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-title">
            <xsl:value-of select="title" />
          </td>
          <td class="field-classname">
            <a>
              <xsl:attribute name="href">
                <xsl:choose>
                  <xsl:when test="_widget_docurl">
                    <xsl:value-of select="_widget_docurl" />
                  </xsl:when>
                  <xsl:otherwise>
                    <xsl:text>http://code.google.com/p/molinos-cms/w/list?q=</xsl:text>
                    <xsl:value-of select="classname" />
                  </xsl:otherwise>
                </xsl:choose>
              </xsl:attribute>
              <xsl:value-of select="classname" />
            </a>
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>


  <!-- вывод файлов -->
  <xsl:template match="data[../@preset = 'files']" mode="mcms_list">
    <xsl:variable name="versions" select="not(not(node/versions/version[@name!='original']))" />

    <thead>
      <tr>
        <th colspan="2"/>
        <th>Имя файла</th>
        <xsl:if test="$versions">
          <th>Версии</th>
        </xsl:if>
        <th/>
        <th class="field-filesize">Размер</th>
        <th>Дата добавления</th>
      </tr>
    </thead>
    <tbody>
      <xsl:if test="/page/@picker">
        <xsl:attribute name="class">
          <xsl:text>picker</xsl:text>
        </xsl:attribute>
        <xsl:attribute name="id">
          <xsl:value-of select="/page/@picker" />
        </xsl:attribute>
      </xsl:if>
      <xsl:for-each select="node">
        <tr id="file-{@id}">
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <xsl:if test="versions/version[@name='original']">
              <a class="picker icon-download" href="{versions/version[@name='original']/@url}"></a>
            </xsl:if>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <xsl:if test="$versions">
            <td class="versions">
              <xsl:for-each select="versions/version[@name!='original']">
                <a href="{@url}">
                  <xsl:value-of select="@name" />
                </a>
              </xsl:for-each>
            </td>
          </xsl:if>
          <td>
            <xsl:if test="width and height">
              <xsl:value-of select="width" />
              <xsl:text>x</xsl:text>
              <xsl:value-of select="height" />
            </xsl:if>
          </td>
          <td class="field-filesize">
            <xsl:value-of select="filesize" />
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


  <!-- базовый вывод документов -->
  <xsl:template match="data" mode="mcms_list">
    <xsl:variable name="showtype" select="not(../@type)" />
    <thead>
      <tr>
        <th colspan="2"/>
        <th>Заголовок</th>
        <xsl:if test="$showtype">
          <th>Тип</th>
        </xsl:if>
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
          <xsl:if test="$showtype">
            <td class="field-class">
              <xsl:value-of select="@class" />
            </td>
          </xsl:if>
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
    <xsl:param name="depth" />
    <td class="field-name">
      <a class="picker" href="?q=admin.rpc&amp;action=edit&amp;cgroup={/page/@cgroup}&amp;node={@id}&amp;destination={/page/request/@uri}">
        <xsl:if test="$depth">
          <xsl:attribute name="style">
            <xsl:text>padding-left:</xsl:text>
            <xsl:value-of select="$depth * 10" />
            <xsl:text>px</xsl:text>
          </xsl:attribute>
        </xsl:if>
        <xsl:value-of select="@name" />
      </a>
    </td>
  </xsl:template>

  <xsl:template match="node" mode="mcms_list_author">
    <td class="field-uid">
      <xsl:if test="uid">
        <a href="?q=admin.rpc&amp;action=edit&amp;cgroup=access&amp;node={@id}&amp;destination={/page/request/@uri}">
          <xsl:apply-templates select="uid" mode="username" />
        </a>
      </xsl:if>
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
      <u class="fakelink selink select-{@name}">
        <xsl:value-of select="@title" />
      </u>
    </xsl:template>

    <xsl:template match="action" mode="mcms_list_mass_controls">
      <xsl:choose>
        <xsl:when test="position() = last() and position() != 1">
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
    <form method="post" action="?q=admin.rpc&amp;action=search&amp;from={/page/request/@uri}">
      <input type="hidden" name="search_from" value="{/page/@url}" />
      <div class="tb_1">
        <div class="ctrl_left">
          <a class="newlink" href="?q=admin.rpc&amp;action=create&amp;cgroup={/page/@cgroup}&amp;type={@type}&amp;destination={/page/request/@uri}">Добавить</a>
          <xsl:text> | </xsl:text>
          <input type="text" name="search_term" class="search_field" value="{/page/request/getArgs/arg[@name='search']}" />
          <input type="submit" value="Найти" />
          <xsl:text> | </xsl:text>
          <a href="?q=admin.rpc&amp;action=search&amp;cgroup={/page/@cgroup}&amp;destination={/page/request/@uri}">Расширенный поиск</a>
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
      <xsl:if test="not(@published)">
        <xsl:text> unpublished</xsl:text>
      </xsl:if>
    </xsl:attribute>
    <td class="selector">
      <input type="checkbox" name="nodes[]" value="{@id}" />
    </td>
  </xsl:template>


  <!-- подавление неопознанных блоков -->
  <xsl:template match="block" mode="content">
  </xsl:template>


  <!-- форматирование даты -->
  <xsl:template name="FormatDate">
    <xsl:param name="timestamp" />
    <xsl:if test="$timestamp">
      <xsl:value-of select="substring($timestamp,9,2)" />
      <xsl:text>.</xsl:text>
      <xsl:value-of select="substring($timestamp,6,2)" />
      <xsl:text>.</xsl:text>
      <xsl:value-of select="substring($timestamp,3,2)" />
      <xsl:text>, </xsl:text>
      <xsl:value-of select="substring($timestamp,12,5)" />
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>

<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <!-- список документов -->
  <xsl:template match="content[@name = 'list' or @name = 'tree']" mode="content">
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

        <form id="nodeList" method="post" action="?q=nodeapi.rpc&amp;destination={/page/@back}">
          <fieldset>
            <input id="nodeListCommand" type="hidden" name="action" value="" />
          </fieldset>
          <table class="nodes">
            <xsl:apply-templates select="data" mode="mcms_list" />
          </table>
        </form>
        <xsl:apply-templates select="massctl" mode="mcms_list" />
      </xsl:if>
    </div>
  </xsl:template>

  <!-- вывод разделов -->
  <xsl:template match="data[../@preset = 'taxonomy']" mode="mcms_list">
    <xsl:variable name="edit" select="../@edit" />
    <thead>
      <tr>
        <th colspan="4" />
        <xsl:if test="$edit">
          <th />
        </xsl:if>
        <th>Название</th>
      </tr>
    </thead>
    <tbody>
      <xsl:apply-templates select="node" mode="mcms_taxonomy_tree">
        <xsl:with-param name="depth" select="0" />
        <xsl:with-param name="edit" select="$edit" />
      </xsl:apply-templates>
    </tbody>
  </xsl:template>

  <xsl:template match="node" mode="mcms_taxonomy_tree">
    <xsl:param name="depth" />
    <xsl:param name="edit" />
    <tr>
      <xsl:call-template name="odd_row" />
      <td class="icon">
        <a class="icon-add" title="Добавить подраздел" href="admin/create/tag/{@id}?destination={/page/@back}">
          <span/>
        </a>
      </td>
      <td class="icon">
        <xsl:if test="position() != 1">
          <a class="icon-raise" title="Поднять раздел" href="?q=nodeapi.rpc&amp;action=raise&amp;node={@id}&amp;destination={/page/@back}">
            <span/>
          </a>
        </xsl:if>
      </td>
      <td class="icon">
        <xsl:if test="position() != last()">
          <a class="icon-sink" title="Опустить раздел" href="?q=nodeapi.rpc&amp;action=sink&amp;node={@id}&amp;destination={/page/@back}">
            <span/>
          </a>
        </xsl:if>
      </td>
      <xsl:if test="$edit">
        <td class="icon">
          <a class="icon-edit" href="admin/edit/{@id}?destination={/page/@back}">
            <span/>
          </a>
        </td>
      </xsl:if>
      <xsl:apply-templates select="." mode="mcms_list_name">
        <xsl:with-param name="depth" select="$depth" />
        <xsl:with-param name="href" select="concat('admin/content/list?search=tags%3A',@id)" />
      </xsl:apply-templates>
    </tr>
    <xsl:apply-templates select="children/node" mode="mcms_taxonomy_tree">
      <xsl:with-param name="depth" select="$depth + 1" />
    </xsl:apply-templates>
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
            <a class="icon-edit" href="?q=admin/edit/{@id}&amp;destination={/page/@back}" />
          </td>
          <td class="field-name">
            <a href="?q=admin/structure/domains/{@name}">
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
            <a class="icon-add" href="admin/create/domain/{@id}?destination={/page/@back}" />
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


  <!-- вывод файлов -->
  <xsl:template match="data[../@preset = 'files']" mode="mcms_list">
    <xsl:variable name="versions" select="not(not(node/versions/version[@name!='original']))" />

    <thead>
      <tr>
        <th colspan="3"/>
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
          <xsl:call-template name="dump_icon" />
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
            <xsl:choose>
              <xsl:when test="width and height">
                <xsl:value-of select="width" />
                <xsl:text>x</xsl:text>
                <xsl:value-of select="height" />
                <xsl:if test="duration">
                  <xsl:text>, </xsl:text>
                  <xsl:value-of select="duration" />
                </xsl:if>
              </xsl:when>
              <xsl:when test="duration">
                <xsl:value-of select="duration" />
              </xsl:when>
            </xsl:choose>
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
        <th colspan="3"/>
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
            <!--
            <a class="icon-locate" title="Найти документ на сайте" href="?q=nodeapi.rpc&amp;action=locate&amp;node={@id}">
            -->
            <a class="icon-locate" title="Найти документ на сайте" href="?q=node/{@id}">
              <span/>
            </a>
          </td>
          <xsl:call-template name="dump_icon" />
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
    <xsl:param name="href" select="concat('admin/edit/',@id,'?destination=',/page/@back)" />
    <td class="field-name">
      <a class="picker" href="{$href}">
        <xsl:if test="$depth">
          <xsl:attribute name="style">
            <xsl:text>padding-left:</xsl:text>
            <xsl:value-of select="$depth * 10" />
            <xsl:text>px</xsl:text>
          </xsl:attribute>
        </xsl:if>
        <xsl:value-of select="@name" />
        <xsl:if test="not(@name)">
          <xsl:text>(без названия)</xsl:text>
        </xsl:if>
      </a>
    </td>
  </xsl:template>

  <xsl:template match="node" mode="mcms_list_author">
    <td class="field-uid">
      <xsl:if test="uid">
        <a href="?q=admin/edit/{@id}&amp;destination={/page/@back}">
          <xsl:apply-templates select="uid" mode="username" />
        </a>
      </xsl:if>
    </td>
  </xsl:template>

  
  <!-- Выбор нескольких строк таблицы. -->
  <xsl:template match="massctl" mode="mcms_list">
    <div class="nodes-controls-advanced">
      <xsl:if test="../@addlink">
        <a href="{../@addlink}">Добавить</a>
        <xsl:text> | </xsl:text>
      </xsl:if>
      <span>Выбрать: </span>
      <xsl:apply-templates select="selector" mode="mcms_list_mass_controls" />
      <span class="and"> и </span>
      <xsl:apply-templates select="action" mode="mcms_list_mass_controls" />
      <xsl:text>.</xsl:text>
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
      <span class="fakelink selink select-{@name}">
        <xsl:value-of select="@title" />
      </span>
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
      <span class="fakelink actionlink action-{@name}">
        <xsl:value-of select="@title" />
      </span>
    </xsl:template>

  <!-- Форма поиска. -->
  <xsl:template name="mcms_list_search" mode="mcms_list">
    <div class="nodes-controls-basic">
    <form method="post" action="?q=admin.rpc&amp;action=search&amp;from={/page/@back}">
    	<fieldset>
       <input type="hidden" name="search_from" value="{/page/@url}" />
          <a class="newlink">
            <xsl:attribute name="href">
              <xsl:text>?q=admin/create</xsl:text>
              <xsl:if test="@type">
                <xsl:text>/</xsl:text>
                <xsl:value-of select="@type" />
              </xsl:if>
              <xsl:text>&amp;destination=</xsl:text>
              <xsl:value-of select="/page/@back" />
            </xsl:attribute>
            <xsl:text>Добавить</xsl:text>
          </a>
          <xsl:text> | </xsl:text>
          <input type="text" name="search_term" class="search_field" value="{/page/request/getArgs/arg[@name='search']}" />
          <input type="submit" value="Найти" />
          <xsl:text> | </xsl:text>
          <a href="?q=admin.rpc&amp;action=search&amp;cgroup={/page/@cgroup}&amp;destination={/page/@back}">Расширенный поиск</a>
        </fieldset>
	</form>
    </div>
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
  <xsl:template match="content" mode="content">
  </xsl:template>


  <xsl:template name="dump_icon">
    <td>
      <xsl:if test="/page/@debug">
        <xsl:attribute name="class">
          <xsl:text>icon</xsl:text>
        </xsl:attribute>
        <a class="icon-dump" href="node/{@id}/dump">
          <span/>
        </a>
      </xsl:if>
    </td>
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
      <xsl:if test="string-length($timestamp) &gt;= 17">
        <xsl:text>, </xsl:text>
        <xsl:value-of select="substring($timestamp,12,5)" />
      </xsl:if>
    </xsl:if>
  </xsl:template>
</xsl:stylesheet>

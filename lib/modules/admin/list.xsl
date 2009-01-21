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
            <a class="icon-zoom" title="Найти все документы пользователя" href="?q=admin.rpc&amp;action=list&amp;cgroup=content&amp;search=uid%3A{@id}">
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

  <!-- вывод разделов -->
  <xsl:template match="data[../@preset = 'taxonomy']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="5" />
        <th>Название</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-add" title="Добавить подраздел" href="?q=admin.rpc&amp;action=create&amp;type=tag&amp;parent={@id}&amp;cgroup={/page/@cgroup}&amp;destination={/page/@urlEncoded}">
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
            <a class="icon-zoom" title="Найти все документы из этого раздела" href="?q=admin.rpc&amp;action=list&amp;cgroup=content&amp;columns=name,class,uid,created&amp;search=tags%3A{@id}">
              <span/>
            </a>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
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
            <a class="icon-zoom" href="?q=admin.rpc&amp;action=list&amp;cgroup=content&amp;type={@name}" />
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
      <xsl:for-each select="node">
        <tr>
          <xsl:call-template name="odd_row" />
          <xsl:choose>
            <xsl:when test="$domains">
              <td class="icon">
                <a class="icon-edit" href="?q=admin.rpc&amp;action=edit&amp;cgroup=structure&amp;node={@id}&amp;destination={/page/@urlEncoded}" />
              </td>
              <td class="field-name">
                <a href="?q=admin.rpc&amp;action=tree&amp;preset=pages&amp;subid={@id}&amp;cgroup={/page/@cgroup}">
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
            </xsl:when>
            <xsl:otherwise>
              <td class="icon">
                <a class="icon-add" href="?q=admin.rpc&amp;action=create&amp;type=domain&amp;parent={@id}&amp;destination={/page/@urlEncoded}" />
              </td>
              <xsl:apply-templates select="." mode="mcms_list_name" />
            </xsl:otherwise>
          </xsl:choose>
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
            <a href="http://code.google.com/p/molinos-cms/wiki/{@classname}">
              <xsl:value-of select="@classname" />
            </a>
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
            <a class="picker icon-download" href="{@_url}"></a>
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
    <td class="field-name">
      <a class="picker" href="?q=admin.rpc&amp;action=edit&amp;cgroup={/page/@cgroup}&amp;node={@id}&amp;destination={/page/@urlEncoded}">
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
      <a href="?q=admin.rpc&amp;action=edit&amp;cgroup=access&amp;node={@id}&amp;destination={/page/@urlEncoded}">
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
      <u class="fakelink selink select-{@name}">
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
    <form method="post" action="?q=admin.rpc&amp;action=search&amp;from={/page/@urlEncoded}">
      <input type="hidden" name="search_from" value="{/page/@url}" />
      <div class="tb_1">
        <div class="ctrl_left">
          <a class="newlink" href="?q=admin.rpc&amp;action=create&amp;cgroup={/page/@cgroup}&amp;type={@type}&amp;destination={/page/@urlEncoded}">Добавить</a>
          <xsl:text> | </xsl:text>
          <input type="text" name="search_term" class="search_field" value="{/page/@search}" />
          <input type="submit" value="Найти" />
          <xsl:text> | </xsl:text>
          <a href="?q=admin.rpc&amp;action=search&amp;cgroup={/page/@cgroup}&amp;destination={/page/@urlEncoded}">Расширенный поиск</a>
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
</xsl:stylesheet>

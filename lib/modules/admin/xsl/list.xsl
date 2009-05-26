<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../template.xsl" />

  <!-- список документов -->
  <xsl:template match="content[@name='list']" mode="content">
    <div class="doclist">
      <h2>
        <xsl:choose>
          <xsl:when test="@typename">
            <a href="admin/node/{@typeid}">
              <xsl:value-of select="@typename" />
            </a>
            <xsl:text>: </xsl:text>
            <xsl:text>список документов</xsl:text>
          </xsl:when>
          <xsl:otherwise>
            <xsl:value-of select="@title" />
          </xsl:otherwise>
        </xsl:choose>
      </h2>

      <xsl:choose>
        <xsl:when test="not(data/node) and not(not($search))">
          <p>Нет таких документов, <a href="{$query}">показать все</a>?</p>
        </xsl:when>
        <xsl:when test="not(data/node)">
          <p>Здесь ничего нет, вообще.</p>
          <xsl:if test="@type">
            <p>
              <a href="admin/create/{@type}">Создать</a>
            </p>
          </xsl:if>
        </xsl:when>
        <xsl:otherwise>
          <xsl:if test="not(@nosearch)">
            <xsl:call-template name="mcms_list_search">
              <xsl:with-param name="advanced" select="@advsearch" />
            </xsl:call-template>
          </xsl:if>

          <!-- action проставляется скриптом lib/modules/admin/scripts/admin/10massctl.js -->
          <form method="post" id="nodeList">
            <input type="hidden" name="sendto" value="{$sendto}" />
            <xsl:apply-templates select="data" mode="massctl">
              <xsl:with-param name="edit" select="@canedit" />
              <xsl:with-param name="create" select="@create" />
            </xsl:apply-templates>
            <table class="nodes">
              <xsl:apply-templates select="data" mode="nodelist" />
            </table>
            <xsl:apply-templates select="data" mode="massctl">
              <xsl:with-param name="edit" select="@canedit" />
              <xsl:with-param name="create" select="@create" />
            </xsl:apply-templates>
          </form>
        </xsl:otherwise>
      </xsl:choose>
    </div>
  </xsl:template>

  <!-- базовый вывод документов -->
  <xsl:template match="data" mode="nodelist">
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
          <xsl:apply-templates select="." mode="trclass" />
          <td class="icon">
            <a class="icon-edit" href="admin/edit/{@id}?destination={$back}">
              <span>Изменить</span>
            </a>
          </td>
          <td class="field-name">
            <a href="admin/node/{@id}">
              <xsl:value-of select="@name" />
            </a>
          </td>
          <xsl:if test="$showtype">
            <td class="field-class">
              <xsl:value-of select="@class" />
            </td>
          </xsl:if>
          <td>
            <xsl:if test="uid/@id">
              <a href="admin/node/{uid/@id}">
                <xsl:value-of select="uid/fullname" />
                <xsl:if test="not(uid/fullname)">
                  <xsl:value-of select="uid/@name" />
                </xsl:if>
              </a>
            </xsl:if>
          </td>
          <td class="field-created">
            <xsl:apply-templates select="@created" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <!-- Массовые операции: селекторы, действия. -->
  <xsl:template match="data" mode="massctl">
    <xsl:param name="publish" select="not(not(node[not(@published)]))" />
    <xsl:param name="hide" select="not(not(node[@published]))" />
    <xsl:param name="edit" select="0" />
    <xsl:param name="create" select="0" />
    <xsl:param name="restore" select="0" />

    <div class="massctl">
      <div class="selectors">
        <xsl:if test="$create">
          <a href="{$create}?destination={$back}">Добавить</a>
          <xsl:text> | </xsl:text>
        </xsl:if>
        <span>Выбрать: </span>
        <span class="fakelink selink select-all">все</span>
        <xsl:text>, </xsl:text>
        <span class="fakelink selink select-none">ни одного</span>
        <xsl:if test="$hide">
          <xsl:text>, </xsl:text>
          <span class="fakelink selink select-published">опубликованные</span>
        </xsl:if>
        <xsl:if test="$publish">
          <xsl:text>, </xsl:text>
          <span class="fakelink selink select-unpublished">скрытые</span>
        </xsl:if>
      </div>
      <div class="actions">
        <span>Выбранные: </span>
        <xsl:choose>
          <xsl:when test="$sendto">
            <span class="fakelink actionlink action-sendto">использовать</span>
          </xsl:when>
          <xsl:otherwise>
            <span class="fakelink actionlink action-delete">удалить</span>
            <xsl:if test="$restore">
              <xsl:text>, </xsl:text>
              <span class="fakelink actionlink action-undelete">восстановить</span>
            </xsl:if>
            <xsl:if test="$publish">
              <xsl:text>, </xsl:text>
              <span class="fakelink actionlink action-publish">опубликовать</span>
            </xsl:if>
            <xsl:if test="$hide">
              <xsl:text>, </xsl:text>
              <span class="fakelink actionlink action-unpublish">скрыть</span>
            </xsl:if>
            <xsl:if test="$edit">
              <xsl:text>, </xsl:text>
              <span class="fakelink actionlink action-edit">редактировать</span>
            </xsl:if>
          </xsl:otherwise>
        </xsl:choose>
      </div>
    </div>
  </xsl:template>

  <!-- Добавляет строке таблицы классы, вроде published. -->
  <xsl:template match="node" mode="trclass">
    <xsl:attribute name="class">
      <xsl:if test="not(@published)">un</xsl:if>
      <xsl:text>published</xsl:text>
    </xsl:attribute>
    <td class="selector">
      <xsl:choose>
        <xsl:when test="$sendto">
          <input type="radio" name="selected" value="{@id}" />
        </xsl:when>
        <xsl:otherwise>
          <input type="checkbox" name="selected[]" value="{@id}" />
        </xsl:otherwise>
      </xsl:choose>
    </td>
  </xsl:template>

  <!-- Форма поиска. -->
  <xsl:template name="mcms_list_search" mode="mcms_list">
    <xsl:param name="advanced" select="1" />

    <div class="nodes-controls-basic">
    <form method="post" action="?q=admin/search&amp;from={/page/@back}">
      <fieldset>
        <input type="hidden" name="search_from" value="{/page/@url}" />
          <xsl:if test="@preset!='trash'">
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
          </xsl:if>
          <input type="text" name="search_term" class="search_field" value="{/page/request/getArgs/arg[@name='search']}" />
          <input type="submit" value="Найти" />
          <xsl:if test="$advanced">
            <xsl:text> | </xsl:text>
            <a href="?q=admin/search&amp;destination={/page/@back}">Расширенный поиск</a>
          </xsl:if>
        </fieldset>
      </form>
    </div>
  </xsl:template>

  <!-- подавление неопознанных блоков -->
  <xsl:template match="content" mode="content" />
</xsl:stylesheet>

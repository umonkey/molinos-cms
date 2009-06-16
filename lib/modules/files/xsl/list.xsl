<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="lib.xsl" />
  <xsl:import href="../../admin/xsl/list.xsl" />

  <xsl:template match="content" mode="content">
    <xsl:variable name="sendto" select="/page/request/getArgs/arg[@name='sendto']" />

    <div class="doclist filelist">
      <h2>Файловый архив</h2>

      <xsl:choose>
        <xsl:when test="data/node">
          <div class="nodes-viewmodes">
            <span class="presentations">Вид: <a class="table" href="admin/content/files?mode=table&amp;scope={@scope}&amp;page={$page}&amp;search={$search}&amp;sendto={$sendto}">таблица</a> <a class="list" href="admin/content/files?mode=icons&amp;scope={@scope}&amp;page={$page}&amp;search={$search}&amp;sendto={$sendto}">иконки</a></span>
            <span>Показывать: </span>
            <xsl:choose>
              <xsl:when test="/page/content/@scope">
                <a href="admin/content/files?mode={@mode}&amp;search={$search}&amp;sendto={$sendto}&amp;destination={$next}">все</a>,
              </xsl:when>
              <xsl:otherwise>
                все,
              </xsl:otherwise>
            </xsl:choose>
            <xsl:choose>
              <xsl:when test="/page/content/@scope='picture'">
                картинки,
              </xsl:when>
              <xsl:otherwise>
                <a href="admin/content/files?mode={@mode}&amp;scope=picture&amp;search={$search}&amp;sendto={$sendto}&amp;destination={$next}">картинки</a>,
              </xsl:otherwise>
            </xsl:choose>
            <xsl:choose>
              <xsl:when test="/page/content/@scope='multimedia'">
                мультимедиа,
              </xsl:when>
              <xsl:otherwise>
                <a href="admin/content/files?mode={@mode}&amp;scope=multimedia&amp;search={$search}&amp;sendto={$sendto}&amp;destination={$next}">мультимедиа</a>,
              </xsl:otherwise>
            </xsl:choose>
            <xsl:choose>
              <xsl:when test="/page/content/@scope = 'office'">
                офис
              </xsl:when>
              <xsl:otherwise>
                <a href="admin/content/files?mode={@mode}&amp;scope=office&amp;search={$search}&amp;sendto={$sendto}&amp;destination={$next}">офис</a>
              </xsl:otherwise>
            </xsl:choose>
          </div>
          <xsl:call-template name="mcms_list_search">
            <xsl:with-param name="advanced" select="@advsearch" />
            <xsl:with-param name="advancedlink">admin/content/files/search</xsl:with-param>
            <xsl:with-param name="createtxt">Загрузить</xsl:with-param>
          </xsl:call-template>
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
        </xsl:when>
        <xsl:when test="$search and @scope">
          <p>Нет таких файлов.  Попробуйте <a href="admin/content/files?mode={@mode}&amp;scope={@scope}&amp;destination={$next}">отменить поиск</a> или поискать «<xsl:value-of select="$search" />» среди файлов <a href="admin/content/files?mode={@mode}&amp;search={$search}&amp;destination={$next}">любого типа</a>.</p>
        </xsl:when>
        <xsl:when test="$search">
          <p>Нет таких файлов, попробуйте <a href="admin/content/files?mode={@mode}&amp;scope={@scope}&amp;destination={$next}">отменить поиск</a>.</p>
        </xsl:when>
        <xsl:when test="@scope">
          <p>Нет файлов такого типа, <a href="admin/content/files?mode={@mode}&amp;search={$search}&amp;destination={$next}">показать все файлы</a>?</p>
        </xsl:when>
        <xsl:otherwise>
        </xsl:otherwise>
      </xsl:choose>
    </div>
  </xsl:template>

  <xsl:template match="data[../@mode='icons']" mode="nodelist">
    <ul class="files nodes">
      <xsl:for-each select="node">
        <li>
          <a class="thumbnail" href="admin/node/{@id}?destination={$back}">
            <xsl:apply-templates select="." mode="thumbnail" />
          </a>
          <label>
            <input type="checkbox" name="selected[]" value="{@id}" />
            <a href="admin/node/{@id}">
              <xsl:value-of select="@name" />
            </a>
          </label>
        </li>
      </xsl:for-each>
    </ul>
  </xsl:template>

  <xsl:template match="data[../@mode='table']" mode="nodelist">
    <thead>
      <tr>
        <th/>
        <th/>
        <th/>
        <th>Имя файла</th>
        <th>Хост</th>
        <th>Объём</th>
        <th>Владелец</th>
        <th>Добавлен</th>
      </tr>
    </thead>
    <tbody id="filelist">
      <xsl:for-each select="node">
        <tr>
          <xsl:apply-templates select="." mode="trclass" />
          <td class="icon">
            <a class="icon-download" href="download/{@id}/{filename}">
              <span>Скачать</span>
            </a>
          </td>
          <td class="icon">
            <a class="icon-edit" href="admin/edit/{@id}?destination={/page/@back}">
              <span>Изменить</span>
            </a>
          </td>
          <td class="field-name">
            <a href="admin/node/{@id}?destination={$back}">
              <xsl:apply-templates select="." mode="thumbnail">
                <xsl:with-param name="size">16</xsl:with-param>
              </xsl:apply-templates>
              <span><xsl:value-of select="@name" /></span>
            </a>
          </td>
          <td>
            <xsl:value-of select="remoteurl/@host" />
          </td>
          <td class="r">
            <xsl:call-template name="filesize">
              <xsl:with-param name="size" select="filesize" />
            </xsl:call-template>
          </td>
          <td>
            <xsl:apply-templates select="uid" />
          </td>
          <td>
            <xsl:apply-templates select="@created" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <xsl:template match="data[../@type='file']" mode="mcms_list">
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
              <a class="picker icon icon-download" href="{versions/version[@name='original']/@url}"></a>
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
            <xsl:call-template name="filesize">
              <xsl:with-param name="size" select="filesize" />
            </xsl:call-template>
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

  <xsl:template match="uid">
    <a href="admin/node/{@id}">
      <xsl:value-of select="fullname" />
      <xsl:if test="not(fullname)">
        <xsl:value-of select="@name" />
      </xsl:if>
    </a>
  </xsl:template>
</xsl:stylesheet>

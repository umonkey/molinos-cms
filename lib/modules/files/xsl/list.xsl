<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/xsl/list.xsl" />

  <xsl:template match="data[../@name='list' and ../@preset='files']" mode="nodelist">
    <thead>
      <tr>
        <th/>
        <th/>
        <th/>
        <th>Имя файла</th>
        <th>Объём</th>
        <th>Добавлен</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:apply-templates select="." mode="trclass" />
          <td class="icon">
            <a class="icon-download" href="{../../@path}/{filepath}">
              <span>Скачать</span>
            </a>
          </td>
          <td class="icon">
            <a class="icon-edit" href="admin/edit/{@id}?destination={/page/@back}">
              <span>Изменить</span>
            </a>
          </td>
          <td class="field-name">
            <a href="admin/node/{@id}">
              <xsl:attribute name="class">
                <xsl:text>ft-</xsl:text>
                <xsl:choose>
                  <xsl:when test="contains(filetype, 'image/')">image</xsl:when>
                  <xsl:when test="contains(filetype, 'video/')">video</xsl:when>
                  <xsl:when test="contains(filetype, 'text/')">text</xsl:when>
                  <xsl:otherwise>binary</xsl:otherwise>
                </xsl:choose>
              </xsl:attribute>
              <xsl:value-of select="@name" />
            </a>
          </td>
          <td class="r">
            <xsl:call-template name="filesize">
              <xsl:with-param name="size" select="filesize" />
            </xsl:call-template>
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
</xsl:stylesheet>

<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content[@name='editfiles']" mode="content">
    <h2>
      <xsl:value-of select="@title" />
    </h2>
    <form id="editfiles" method="post" action="{@action}" enctype="multipart/form-data">
      <table>
        <tbody>
          <xsl:for-each select="node">
            <tr>
              <td rowspan="3">
                <xsl:apply-templates select="filetype" mode="classname">
                  <xsl:with-param name="prefix">preview</xsl:with-param>
                </xsl:apply-templates>
                <xsl:apply-templates select="versions/version[@name='thumbnail']" />
              </td>
              <td class="r">Имя:</td>
              <td>
                <input type="text" name="files[{@id}][name]" value="{@name}" class="text" />
              </td>
            </tr>
            <tr>
              <td class="r">Метки:</td>
              <td>
                <input type="text" name="files[{@id}][labels]" value="{@labels}" class="text" />
              </td>
            </tr>
            <tr>
              <td colspan="2"> </td>
            </tr>
          </xsl:for-each>
          <xsl:if test="count(node) &gt; 1">
            <tr>
              <td colspan="2" class="r">Метки для всех файлов:</td>
              <td>
                <input type="text" name="labels" class="text" />
              </td>
            </tr>
          </xsl:if>
          <tr>
            <td colspan="2" />
            <td>
              <input type="submit" value="Сохранить" />
            </td>
          </tr>
        </tbody>
      </table>
    </form>
  </xsl:template>

  <xsl:template match="version">
    <img src="{@url}" width="{@width}" height="{@height}" alt="{@name}" />
  </xsl:template>

  <xsl:template match="filetype" mode="classname">
    <xsl:param name="prefix" />
    <xsl:attribute name="class">
      <xsl:value-of select="$prefix" />
      <xsl:if test="$prefix">
        <xsl:text> </xsl:text>
      </xsl:if>
      <xsl:text>ft-</xsl:text>
      <xsl:choose>
        <xsl:when test="contains(filetype, 'audio/')">audio</xsl:when>
        <xsl:when test="contains(filetype, 'image/')">image</xsl:when>
        <xsl:when test="contains(filetype, 'video/')">video</xsl:when>
        <xsl:when test="contains(filetype, 'text/')">text</xsl:when>
        <xsl:otherwise>binary</xsl:otherwise>
      </xsl:choose>
    </xsl:attribute>
  </xsl:template>
</xsl:stylesheet>

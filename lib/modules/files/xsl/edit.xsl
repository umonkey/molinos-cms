<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="lib.xsl" />
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content[@name='editfiles']" mode="content">
    <h2>
      <xsl:value-of select="@title" />
    </h2>
    <form id="editfiles" method="post" action="{@action}" enctype="multipart/form-data">
      <table>
        <tbody>
          <xsl:apply-templates select="." mode="saveline" />
          <xsl:for-each select="node">
            <tr>
              <td rowspan="3">
                <a href="admin/node/{@id}?destination={$back}">
                  <xsl:apply-templates select="." mode="thumbnail" />
                </a>
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
          <xsl:apply-templates select="." mode="saveline" />
        </tbody>
      </table>
    </form>
  </xsl:template>

  <xsl:template match="content" mode="saveline">
    <tr class="okbtn">
      <td colspan="2" />
      <td>
        <input type="submit" value="Сохранить" />
        <xsl:if test="count(node[contains(filetype,'image/')]) &gt; count(node[contains(filetype,'image/') and versions/version[@width=100 and @height=100]])">
          <xsl:text> или </xsl:text>
          <a href="admin/files/update-icons?files={@ids}&amp;destination={$back}">обновить иконки</a>
        </xsl:if>
      </td>
    </tr>
  </xsl:template>
</xsl:stylesheet>

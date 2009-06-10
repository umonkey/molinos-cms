<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="lib.xsl" />
  <xsl:import href="../../admin/template.xsl" />

  <xsl:template match="content" mode="content">
    <h2>
      <xsl:value-of select="node/@name" />
    </h2>

    <xsl:apply-templates select="files" />
    <xsl:if test="not(files/node)">
      <p>Нет файлов, прикреплённых к этому документу. <a href="admin/content/files?sendto={node/@id}&amp;destination={$next}">Добавить</a>?</p>
    </xsl:if>
  </xsl:template>

  <xsl:template match="files[node]">
    <form method="post">
      <table class="classic" id="extrafiles">
        <thead>
          <tr>
            <th/>
            <th>Имя файла</th>
            <th>Тип</th>
            <th>Объём</th>
          </tr>
        </thead>
        <xsl:for-each select="node">
          <tr>
            <td>
              <input type="checkbox" name="remove[]" value="{@id}" />
            </td>
            <td class="tn">
              <a href="admin/node/{@id}?destination={$back}">
                <xsl:apply-templates select="." mode="thumbnail">
                  <xsl:with-param name="size">16</xsl:with-param>
                </xsl:apply-templates>
                <span>
                  <xsl:value-of select="@name" />
                </span>
              </a>
            </td>
            <td>
              <xsl:value-of select="filetype" />
            </td>
            <td class="r">
              <xsl:call-template name="filesize">
                <xsl:with-param name="size" select="filesize" />
              </xsl:call-template>
            </td>
          </tr>
        </xsl:for-each>
      </table>
      <input type="submit" value="Открепить выделенные" /> или <a href="admin/content/files?sendto={../node/@id}&amp;destination={$back}">добавить ещё файлов</a> или <a href="{$next}">вернуться</a>
    </form>
  </xsl:template>
</xsl:stylesheet>

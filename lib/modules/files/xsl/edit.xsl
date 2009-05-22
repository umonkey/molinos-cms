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
                <a href="{../@path}/{filepath}">
                  <img src="lib/modules/admin/styles/admin/images/user.png" />
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
</xsl:stylesheet>

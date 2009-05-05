<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

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
              <a class="icon-edit" href="?q=admin/edit/{@id}&amp;destination={/page/@back}" />
            </td>
            <td>
              <a href="?q=admin/content/list/{@name}">
                <xsl:value-of select="title" />
              </a>
            </td>
          </tr>
        </xsl:if>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>

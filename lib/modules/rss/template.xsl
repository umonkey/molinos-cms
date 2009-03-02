<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../admin/template.xsl" />

  <!-- вывод трансформаторов -->
  <xsl:template match="data[../@type='rssfeed']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="3"/>
        <th>Имя</th>
        <th>Заголовок</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr id="file-{@id}">
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-feed" href="?q={@name}.rss">
              <span/>
            </a>
          </td>
          <td class="icon">
            <a class="icon-validate" href="http://feedvalidator.org/check.cgi?url={/page/@base}{@name}.rss" target="_blank">
              <span/>
            </a>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td>
            <xsl:value-of select="title" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>

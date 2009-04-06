<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../admin/template.xsl" />

  <xsl:template match="data[@mode='syslog']" mode="mcms_list">
    <thead>
      <tr>
        <th>Дата</th>
        <th>Документ</th>
        <th>Действие</th>
        <th>Пользователь</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <td class="field-name">
            <a href="admin/edit/{@nid}?destination={/page/@back}">
              <xsl:value-of select="@name" />
            </a>
          </td>
          <td>
            <xsl:value-of select="@operation" />
          </td>
          <td class="field-uid">
            <a href="admin/edit/{@uid}?destination={/page/@back}">
              <xsl:value-of select="@username" />
            </a>
          </td>
          <td>
            <xsl:call-template name="FormatDate">
              <xsl:with-param name="timestamp" select="@timestamp" />
            </xsl:call-template>
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>

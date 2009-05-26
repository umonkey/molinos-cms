<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/xsl/list.xsl" />

  <xsl:template match="content" mode="content">
    <h2>
      <xsl:value-of select="@title" />
    </h2>
    <table class="classic wide">
      <thead>
        <tr>
          <th>Дата</th>
          <th class="wide">Объект</th>
          <th>Пользователь</th>
          <th>IP адрес</th>
          <th>Действие</th>
        </tr>
      </thead>
      <tbody>
        <xsl:for-each select="data/node">
          <tr>
            <td class="nw">
              <xsl:value-of select="@timestamp" />
            </td>
            <td>
              <a href="admin/node/{@nid}">
                <xsl:value-of select="@name" />
              </a>
            </td>
            <td>
              <xsl:value-of select="@operation" />
            </td>
            <td>
              <a href="admin/node/{@uid}">
                <xsl:value-of select="@username" />
              </a>
            </td>
            <td>
              <xsl:value-of select="@ip" />
            </td>
          </tr>
        </xsl:for-each>
      </tbody>
    </table>
  </xsl:template>
</xsl:stylesheet>

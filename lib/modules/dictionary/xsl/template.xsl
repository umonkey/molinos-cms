<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/xsl/list.xsl" />

  <!-- вывод справочников -->
  <xsl:template match="data" mode="nodelist">
    <thead>
      <tr>
        <th colspan="3" />
        <th>Имя</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <xsl:sort select="@title" />
        <tr>
          <xsl:apply-templates select="." mode="trclass" />
          <td class="icon">
            <a class="icon-edit" href="?q=admin/edit/{@id}&amp;destination={/page/@back}" />
          </td>
          <td class="icon">
            <a class="icon-validate" href="admin/structure/fields?type={@name}" />
          </td>
          <td>
            <a href="?q=admin/content/list/{@name}">
              <xsl:value-of select="@title" />
            </a>
            <xsl:apply-templates select="@count" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>

  <xsl:template match="@count">
    <xsl:text> (</xsl:text>
    <xsl:value-of select="." />
    <xsl:text>)</xsl:text>
  </xsl:template>
</xsl:stylesheet>

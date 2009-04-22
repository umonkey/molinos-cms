<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <!-- список полей -->
  <xsl:template match="data[../@type='field']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="1" />
        <th>№</th>
        <th>Имя</th>
        <th>Название</th>
        <th>Тип</th>
        <th>Индекс</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <xsl:sort select="weight" data-type="number" />
        <xsl:sort select="@name" data-type="text" />
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="r">
            <xsl:value-of select="weight" />
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td>
            <xsl:value-of select="label" />
          </td>
          <td>
            <xsl:value-of select="type" />
          </td>
          <td>
            <xsl:if test="indexed">
              <xsl:text>X</xsl:text>
            </xsl:if>
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>

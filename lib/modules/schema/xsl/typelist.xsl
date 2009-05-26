<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:template match="data[../@preset='schema']" mode="nodelist">
    <thead>
      <tr>
        <th colspan="3" />
        <th>Название</th>
        <th>Имя</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <xsl:sort select="@published" order="descending" />
        <xsl:sort select="@title" />
        <tr>
          <xsl:apply-templates select="." mode="trclass" />
          <td class="icon">
            <a class="icon-zoom" href="admin/content/list/{@name}" />
          </td>
          <td class="icon">
            <xsl:if test="@dynamic">
              <a class="icon-validate" href="admin/structure/fields?type={@name}" />
            </xsl:if>
          </td>
          <td class="field-name">
            <xsl:choose>
              <xsl:when test="@name = 'type'">
                <xsl:value-of select="@title" />
              </xsl:when>
              <xsl:otherwise>
                <a href="admin/node/{@id}">
                  <xsl:value-of select="@title" />
                </a>
              </xsl:otherwise>
            </xsl:choose>
            <xsl:if test="@count">
              <xsl:text> (</xsl:text>
              <xsl:value-of select="@count" />
              <xsl:text>)</xsl:text>
            </xsl:if>
          </td>
          <td>
            <xsl:value-of select="@name" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>

<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/xsl/list.xsl" />

  <xsl:template match="data[../@name='list' and ../@preset='groups']" mode="nodelist">
    <thead>
      <tr>
        <th/>
        <th/>
        <th>Название</th>
        <th>Добавлена</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:apply-templates select="." mode="trclass" />
          <td class="icon">
            <xsl:if test="@editable">
              <a class="icon-edit" href="admin/edit/{@id}?destination={/page/@back}">
                <span>Изменить</span>
              </a>
            </xsl:if>
          </td>
          <td class="field-name">
            <a href="admin/node/{@id}">
              <xsl:value-of select="fullname" />
              <xsl:if test="not(fullname)">
                <xsl:value-of select="@name" />
              </xsl:if>
            </a>
            <xsl:if test="@users">
              <xsl:text> (</xsl:text>
              <xsl:value-of select="@users" />
              <xsl:text>)</xsl:text>
            </xsl:if>
          </td>
          <td>
            <xsl:apply-templates select="@created" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>

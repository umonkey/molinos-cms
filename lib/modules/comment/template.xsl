<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../admin/xsl/list.xsl" />

  <xsl:template match="data" mode="nodelist">
    <xsl:variable name="showtype" select="not(../@type)" />
    <xsl:variable name="authors" select="not(not(node/uid))"  />
    <thead>
      <tr>
        <th colspan="2"/>
        <th>Текст</th>
        <th>К чему</th>
        <th>Автор</th>
        <th>Дата добавления</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:apply-templates select="." mode="trclass" />
          <td class="icon">
            <a class="icon-edit" href="admin/edit/{@id}?destination={$back}">
              <span>Изменить</span>
            </a>
          </td>
          <td class="field-name">
            <a href="admin/node/{@id}?destination={$back}">
              <xsl:choose>
                <xsl:when test="text/@snippet">
                  <xsl:value-of select="text/@snippet" />
                </xsl:when>
                <xsl:otherwise>
                  <xsl:value-of select="substring(text/text(),0,50)" />
                </xsl:otherwise>
              </xsl:choose>
            </a>
          </td>
          <td>
            <a href="admin/node/{node/@id}?destination={$back}">
              <xsl:value-of select="node/@name" />
            </a>
          </td>
          <td>
            <xsl:choose>
              <xsl:when test="uid/@id">
                <a href="admin/node/{uid/@id}">
                  <xsl:value-of select="uid/fullname" />
                  <xsl:if test="not(uid/fullname)">
                    <xsl:value-of select="uid/@name" />
                  </xsl:if>
                </a>
              </xsl:when>
              <xsl:when test="uid/@name">
                <xsl:value-of select="uid/@name" />
              </xsl:when>
              <xsl:otherwise>анонимно</xsl:otherwise>
            </xsl:choose>
          </td>
          <td class="field-created">
            <xsl:apply-templates select="@created" />
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>

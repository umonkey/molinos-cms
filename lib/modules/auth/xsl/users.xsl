<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/xsl/list.xsl" />

  <xsl:template match="data[../@name='list' and ../@preset='users']" mode="nodelist">
    <thead>
      <tr>
        <th/>
        <th/>
        <th>Полное имя</th>
        <th>Логин</th>
        <th>Email</th>
        <th>Добавлен</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
        <tr>
          <xsl:apply-templates select="." mode="trclass" />
          <td class="icon">
            <a class="icon-edit" href="admin/edit/{@id}?destination={/page/@back}">
              <span>Изменить</span>
            </a>
          </td>
          <td class="field-name">
            <a href="admin/node/{@id}">
              <xsl:value-of select="fullname" />
              <xsl:if test="not(fullname)">
                <xsl:value-of select="@name" />
              </xsl:if>
            </a>
          </td>
          <td>
            <a>
              <xsl:attribute name="href">
                <xsl:if test="contains(@name,'@')">
                  <xsl:text>mailto:</xsl:text>
                </xsl:if>
                <xsl:value-of select="@name" />
              </xsl:attribute>
              <xsl:value-of select="@name" />
            </a>
          </td>
          <td>
            <xsl:if test="email">
              <a href="mailto:{email}">
                <xsl:value-of select="email" />
              </a>
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

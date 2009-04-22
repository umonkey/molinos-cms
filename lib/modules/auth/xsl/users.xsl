<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
  <xsl:import href="../../admin/template.xsl" />

  <!-- вывод пользователей -->
  <xsl:template match="data[../@preset = 'users']" mode="mcms_list">
    <thead>
      <tr>
        <th colspan="3"/>
        <th>Идентификатор</th>
        <th>Полное имя</th>
        <th>Email</th>
        <th>Дата регистрации</th>
      </tr>
    </thead>
    <tbody>
      <xsl:for-each select="node">
      <h1><xsl:value-of select="../../../@name" /></h1>
        <tr>
          <xsl:call-template name="odd_row" />
          <td class="icon">
            <a class="icon-zoom" title="Найти все документы пользователя" href="admin/content/list?search=uid%3A{@id}">
              <span/>
            </a>
          </td>
          <td class="icon">
            <xsl:if test="../../@edit and @id != ../../@self">
              <a class="icon-sudo" title="Переключиться в пользователя" href="?q=auth.rpc&amp;action=su&amp;uid={@id}&amp;destination={/page/@back}">
                <span/>
              </a>
            </xsl:if>
          </td>
          <xsl:apply-templates select="." mode="mcms_list_name" />
          <td class="field-fullname">
            <xsl:value-of select="fullname" />
          </td>
          <td class="field-email">
            <xsl:value-of select="email" />
          </td>
          <td class="field-created">
            <xsl:call-template name="FormatDate">
              <xsl:with-param name="timestamp" select="@created" />
            </xsl:call-template>
          </td>
        </tr>
      </xsl:for-each>
    </tbody>
  </xsl:template>
</xsl:stylesheet>
